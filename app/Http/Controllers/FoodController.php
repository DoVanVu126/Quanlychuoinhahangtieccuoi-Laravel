<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class FoodController extends Controller
{
    // --- helper: kiểm tra chứa HTML ---
    protected function containsHtml(string $text): bool
    {
        // Nếu strip_tags khác với input (sau trim) thì có HTML
        return trim(strip_tags($text)) !== trim($text);
    }

    // --- helper: sanitize text (loại bỏ tag, trim) ---
    protected function sanitizeText(?string $text): ?string
    {
        if ($text === null) return null;
        // loại bỏ thẻ html nguy hiểm, giữ plain text
        $clean = trim(strip_tags($text));
        // rút gọn nhiều whitespace
        $clean = preg_replace('/\s+/u', ' ', $clean);
        return $clean;
    }

    // 📌 Lấy danh sách món ăn (có validate page param)
    public function index(Request $request)
    {
        // Kiểm tra page param nếu có
        $pageParam = $request->query('page', 1);

        // phải là số nguyên dương
        if (!ctype_digit((string)$pageParam) || (int)$pageParam < 1) {
            return response()->json(['message' => 'Tham số page không hợp lệ (phải là số nguyên dương).'], 400);
        }

        $perPage = 10;

        $foods = Food::with(['restaurant', 'foodType'])
            ->paginate($perPage, ['*'], 'page', (int)$pageParam);

        // Nếu client yêu cầu page quá lớn (vượt lastPage) -> báo lỗi
        if ($foods->lastPage() > 0 && (int)$pageParam > $foods->lastPage()) {
            return response()->json(['message' => 'Tham số page vượt giới hạn (không có trang này).'], 400);
        }

        // Chuẩn hóa URL ảnh
        $foods->setCollection(
            $foods->getCollection()->map(function ($food) {
                if ($food->image_url && !preg_match('/^https?:\/\//', $food->image_url)) {
                    $food->image_url = asset(trim($food->image_url, '/'));
                }
                return $food;
            })
        );

        return response()->json($foods);
    }

    // 📌 Lấy chi tiết món ăn
    public function show($id)
    {
        $food = Food::with(['restaurant', 'foodType'])->find($id);

        if (!$food) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        if ($food->image_url && !preg_match('/^https?:\/\//', $food->image_url)) {
            $food->image_url = asset(trim($food->image_url, '/'));
        }

        return response()->json($food);
    }

    // 📌 Thêm món ăn (chặt chẽ)
    public function store(Request $request)
    {
        // custom validator để kiểm tra HTML trong text
        $validator = Validator::make($request->all(), [
            'food_type_id' => 'required|exists:food_types,food_type_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240', // chỉ chấp nhận ảnh
        ], [
            'image.mimes' => 'Ảnh phải có định dạng jpg/jpeg/png/gif.',
            'image.max' => 'Kích thước ảnh tối đa 2MB.',
        ]);

        // nếu file người dùng cố tình upload pdf tới field 'image' -> báo lỗi rõ
        if ($request->hasFile('image') && $request->file('image')->getClientMimeType() === 'application/pdf') {
            return response()->json(['message' => 'Không được upload file PDF vào trường ảnh.'], 422);
        }

        // validate cơ bản
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // kiểm tra text fields có chứa HTML hay không -> reject nếu có
        $textFields = ['name', 'unit', 'description'];
        foreach ($textFields as $f) {
            $val = $request->input($f, '');
            if ($val !== null && $this->containsHtml($val)) {
                return response()->json([
                    'message' => "Field '$f' không được chứa HTML/markup. Vui lòng dán plain text.",
                ], 422);
            }

            // kiểm tra chiều dài thực tế (strip_tags) > max (nếu cần)
            $maxLens = ['name' => 150, 'unit' => 50, 'description' => 2000];
            if ($val !== null && mb_strlen($this->sanitizeText($val)) > $maxLens[$f]) {
                return response()->json([
                    'message' => "Độ dài trường '$f' vượt giới hạn ({$maxLens[$f]} ký tự) sau khi loại bỏ HTML.",
                ], 422);
            }
        }

        // chuẩn hóa data
        $data = [
            'food_type_id' => $request->input('food_type_id'),
            'restaurant_id' => $request->input('restaurant_id'),
            'name' => $this->sanitizeText($request->input('name')),
            'description' => $this->sanitizeText($request->input('description')),
            'unit' => $this->sanitizeText($request->input('unit')),
            'price' => $request->input('price'),
        ];

        // Bọc transaction để tránh duplicated partial states
        try {
            $created = DB::transaction(function () use ($request, $data) {
                // upload file nếu có
                if ($request->hasFile('image')) {
                    $file = $request->file('image');

                    // kiểm tra mime type lại một lần nữa
                    $mime = $file->getClientMimeType();
                    if (str_contains($mime, 'pdf')) {
                        throw new \Exception('Không được upload file PDF vào trường ảnh.');
                    }

                    $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/foods'), $fileName);
                    $data['image_url'] = 'uploads/foods/' . $fileName;
                }

                // tạo món ăn (cần có unique constraint ở DB để tránh race condition)
                return Food::create($data);
            });
        } catch (QueryException $e) {
            // duplicate key hoặc ràng buộc DB
            // SQLSTATE 23000 thường là duplicate key
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Bản ghi trùng lặp (tên món có thể đã tồn tại).'], 409);
            }
            // khác
            return response()->json(['message' => 'Lỗi cơ sở dữ liệu.', 'detail' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi lưu món ăn: ' . $e->getMessage()], 500);
        }

        // chuẩn hóa URL ảnh trước khi trả về
        if (!empty($created->image_url)) {
            $created->image_url = asset($created->image_url);
        }

        return response()->json($created, 201);
    }

    // 📌 Cập nhật món ăn
    public function update(Request $request, $id)
    {
        $food = Food::find($id);
        if (!$food) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        $validator = Validator::make($request->all(), [
            'food_type_id' => 'required|exists:food_types,food_type_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:10240',
        ]);

        // nếu file là pdf -> lỗi
        if ($request->hasFile('image') && $request->file('image')->getClientMimeType() === 'application/pdf') {
            return response()->json(['message' => 'Không được upload file PDF vào trường ảnh.'], 422);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // kiểm tra HTML trong các trường text
        $textFields = ['name', 'unit', 'description'];
        foreach ($textFields as $f) {
            $val = $request->input($f, '');
            if ($val !== null && $this->containsHtml($val)) {
                return response()->json([
                    'message' => "Field '$f' không được chứa HTML/markup. Vui lòng dán plain text.",
                ], 422);
            }
            $maxLens = ['name' => 150, 'unit' => 50, 'description' => 2000];
            if ($val !== null && mb_strlen($this->sanitizeText($val)) > $maxLens[$f]) {
                return response()->json([
                    'message' => "Độ dài trường '$f' vượt giới hạn ({$maxLens[$f]} ký tự) sau khi loại bỏ HTML.",
                ], 422);
            }
        }

        // chuẩn hóa data
        $data = [
            'food_type_id' => $request->input('food_type_id'),
            'restaurant_id' => $request->input('restaurant_id'),
            'name' => $this->sanitizeText($request->input('name')),
            'description' => $this->sanitizeText($request->input('description')),
            'unit' => $this->sanitizeText($request->input('unit')),
            'price' => $request->input('price'),
        ];

        // Nếu không có file image trong request thì **không** set image_url => giữ nguyên ảnh cũ
        // Nếu có file image thì upload file mới và xóa file cũ (nếu còn tồn tại)
        try {
            $updated = DB::transaction(function () use ($request, $food, $data) {
                if ($request->hasFile('image')) {
                    // xóa ảnh cũ nếu có
                    if ($food->image_url && file_exists(public_path($food->image_url))) {
                        @unlink(public_path($food->image_url));
                    }

                    $file = $request->file('image');
                    $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/foods'), $fileName);
                    $data['image_url'] = 'uploads/foods/' . $fileName;
                }

                // cập nhật (nếu data không chứa image_url thì giữ nguyên)
                $food->update($data);

                return $food->fresh();
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Bản ghi trùng lặp (tên món có thể đã tồn tại).'], 409);
            }
            return response()->json(['message' => 'Lỗi cơ sở dữ liệu.', 'detail' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi cập nhật: ' . $e->getMessage()], 500);
        }

        if ($updated->image_url) {
            $updated->image_url = asset($updated->image_url);
        }

        return response()->json($updated);
    }

    // 📌 Xóa món ăn (idempotent)
    public function destroy($id)
    {
        // Tìm lại model trong transaction để tránh race condition
        try {
            $deleted = DB::transaction(function () use ($id) {
                $food = Food::find($id);
                if (!$food) {
                    // nếu không tồn tại => trả về 404 (idempotent behavior: lần 2 sẽ 404)
                    return null;
                }

                // xóa file ảnh nếu có
                if ($food->image_url && file_exists(public_path($food->image_url))) {
                    @unlink(public_path($food->image_url));
                }

                $food->delete();
                return true;
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi khi xóa: ' . $e->getMessage()], 500);
        }

        if ($deleted === null) {
            return response()->json(['message' => 'Món ăn không tồn tại hoặc đã bị xóa trước đó'], 404);
        }

        return response()->json(['message' => 'Xóa món ăn thành công']);
    }

    // 📌 Lấy món ăn theo nhà hàng (với sanitize + page param kiểm tra)
    public function getFoodsByRestaurant(Request $request, $restaurant_id)
    {
        $pageParam = $request->query('page', 1);
        if (!ctype_digit((string)$pageParam) || (int)$pageParam < 1) {
            return response()->json(['message' => 'Tham số page không hợp lệ (phải là số nguyên dương).'], 400);
        }

        $perPage = 15;
        $foods = Food::with(['foodType', 'restaurant'])
            ->where('restaurant_id', $restaurant_id)
            ->paginate($perPage, ['*'], 'page', (int)$pageParam);

        if ($foods->lastPage() > 0 && (int)$pageParam > $foods->lastPage()) {
            return response()->json(['message' => 'Tham số page vượt giới hạn (không có trang này).'], 400);
        }

        // chuẩn hóa url ảnh
        $foods = $foods->getCollection()->map(function ($food) {
            if ($food->image_url && !preg_match('/^https?:\/\//', $food->image_url)) {
                $food->image_url = asset(trim($food->image_url, '/'));
            }
            return $food;
        });

        // trả về paginator với collection đã sửa
        $paginated = $foods->toArray();
        return response()->json($paginated);
    }
    public function exportPDF()
{
    $foods = Food::with(['restaurant', 'foodType'])->get();

    $pdf = Pdf::loadView('pdf.foods', compact('foods'))
        ->setPaper('a4', 'portrait');

    return $pdf->download('DanhSachMonAn.pdf');
}
}
