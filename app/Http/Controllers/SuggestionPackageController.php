<?php

namespace App\Http\Controllers;

use App\Models\SuggestionPackage;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SuggestionPackageController extends Controller
{
    // GET /suggestion-packages or /suggestion-packages?restaurant_id=1
    public function index(Request $request)
    {
        $query = SuggestionPackage::query()->with(['foods', 'services', 'hall']);

        // filter by restaurant if provided
        if ($request->has('restaurant_id') && $request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        // filter by event type: match exact or packages that are generic (NULL)
        if ($request->filled('event_type')) {
            $etype = trim($request->query('event_type'));
            $query->where(function ($q) use ($etype) {
                $q->whereNull('event_type')->orWhere('event_type', $etype);
            });
        }

        // filter by number of tables: package.number_of_tables should be >= requested tables or NULL (no restriction)
        if ($request->filled('tables')) {
            $tables = (int) $request->query('tables');
            if ($tables > 0) {
                $query->where(function ($q) use ($tables) {
                    $q->whereNull('number_of_tables')->orWhere('number_of_tables', '>=', $tables);
                });
            }
        }

        $perPage = (int) $request->query('per_page', 20);
        $packages = $query->paginate($perPage);

        return response()->json($packages);
    }

    // GET /suggestion-packages/{id}
    public function show($id)
    {
        $pkg = SuggestionPackage::with(['foods', 'services', 'hall', 'restaurant'])->findOrFail($id);
        return response()->json($pkg);
    }

    // GET /restaurants/{id}/suggestion-packages
    public function byRestaurant(Request $request, $restaurantId)
    {
        $query = SuggestionPackage::with(['foods', 'services', 'hall'])
            ->where('restaurant_id', $restaurantId);

        if ($request->filled('event_type')) {
            $etype = trim($request->query('event_type'));
            $query->where(function ($q) use ($etype) {
                $q->whereNull('event_type')->orWhere('event_type', $etype);
            });
        }

        if ($request->filled('tables')) {
            $tables = (int) $request->query('tables');
            if ($tables > 0) {
                $query->where(function ($q) use ($tables) {
                    $q->whereNull('number_of_tables')->orWhere('number_of_tables', '>=', $tables);
                });
            }
        }

        $packages = $query->get();

        return response()->json($packages);
    }

    // POST /suggestion-packages
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'restaurant_id' => 'nullable|integer|exists:restaurants,restaurant_id',
            'hall_id' => 'nullable|integer|exists:halls,hall_id',
            'event_type' => 'nullable|string|max:100',
            'number_of_tables' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
            'suggestion_foods' => 'nullable|array',
            'suggestion_services' => 'nullable|array',
        ]);

        // ensure explicit null for image_url when absent
        $payload = [
            'name' => $data['name'] ?? null,
            'restaurant_id' => $data['restaurant_id'] ?? null,
            'hall_id' => $data['hall_id'] ?? null,
            'event_type' => $data['event_type'] ?? null,
            'number_of_tables' => $data['number_of_tables'] ?? null,
            'description' => $data['description'] ?? null,
            'image_url' => $data['image_url'] ?? null,
        ];

        DB::beginTransaction();
        try {
            $pkg = SuggestionPackage::create($payload);

            // normalize and sync foods/services (expect array of ids or array of objects {food_id: id})
            if (!empty($data['suggestion_foods'])) {
                $foodIds = collect($data['suggestion_foods'])->map(function ($f) {
                    if (is_array($f)) return $f['food_id'] ?? $f['id'] ?? null;
                    if (is_object($f)) return $f->food_id ?? $f->id ?? null;
                    return $f;
                })->filter()->unique()->values()->all();
                $pkg->foods()->sync($foodIds);
            }

            if (!empty($data['suggestion_services'])) {
                $serviceIds = collect($data['suggestion_services'])->map(function ($s) {
                    if (is_array($s)) return $s['service_id'] ?? $s['id'] ?? null;
                    if (is_object($s)) return $s->service_id ?? $s->id ?? null;
                    return $s;
                })->filter()->unique()->values()->all();
                $pkg->services()->sync($serviceIds);
            }

            DB::commit();

            $pkg->load(['foods', 'services', 'hall', 'restaurant']);
            return response()->json($pkg, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi tạo gói gợi ý', 'error' => $e->getMessage()], 500);
        }
    }

    // PUT/PATCH /suggestion-packages/{id}
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'restaurant_id' => 'nullable|integer|exists:restaurants,restaurant_id',
            'hall_id' => 'nullable|integer|exists:halls,hall_id',
            'event_type' => 'nullable|string|max:100',
            'number_of_tables' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
            'suggestion_foods' => 'nullable|array',
            'suggestion_services' => 'nullable|array',
        ]);

        try {
            $pkg = SuggestionPackage::findOrFail($id);

            $payload = [
                'name' => $data['name'] ?? $pkg->name,
                'restaurant_id' => $data['restaurant_id'] ?? $pkg->restaurant_id,
                'hall_id' => $data['hall_id'] ?? $pkg->hall_id,
                'event_type' => $data['event_type'] ?? $pkg->event_type,
                'number_of_tables' => array_key_exists('number_of_tables', $data) ? $data['number_of_tables'] : $pkg->number_of_tables,
                'description' => $data['description'] ?? $pkg->description,
                // if image_url omitted, keep existing; if explicitly null, set null
                'image_url' => array_key_exists('image_url', $data) ? $data['image_url'] : $pkg->image_url,
            ];

            DB::beginTransaction();

            $pkg->update($payload);

            $pkgId = $pkg->getKey(); // robust to non-standard PK name

            // ---- explicit pivot replace for suggestion_foods ----
            if (array_key_exists('suggestion_foods', $data)) {
                $foodIds = collect($data['suggestion_foods'])->map(function ($f) {
                    if (is_array($f)) return $f['food_id'] ?? $f['id'] ?? null;
                    if (is_object($f)) return $f->food_id ?? $f->id ?? null;
                    return $f;
                })->map(fn($id) => $id === null ? null : (int)$id)
                    ->filter()    // remove falsy / null / 0
                    ->unique()
                    ->values()
                    ->all();

                // delete old pivot rows for this package
                DB::table('suggestion_foods')->where('package_id', $pkgId)->delete();

                // insert new rows (if any)
                if (!empty($foodIds)) {
                    $now = now();
                    $insert = [];
                    foreach ($foodIds as $fid) {
                        $insert[] = [
                            'package_id' => $pkgId,
                            'food_id' => $fid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    DB::table('suggestion_foods')->insert($insert);
                }
            }

            // ---- explicit pivot replace for suggestion_services ----
            if (array_key_exists('suggestion_services', $data)) {
                $serviceIds = collect($data['suggestion_services'])->map(function ($s) {
                    if (is_array($s)) return $s['service_id'] ?? $s['id'] ?? null;
                    if (is_object($s)) return $s->service_id ?? $s->id ?? null;
                    return $s;
                })->map(fn($id) => $id === null ? null : (int)$id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                DB::table('suggestion_services')->where('package_id', $pkgId)->delete();

                if (!empty($serviceIds)) {
                    $now = now();
                    $insert = [];
                    foreach ($serviceIds as $sid) {
                        $insert[] = [
                            'package_id' => $pkgId,
                            'service_id' => $sid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    DB::table('suggestion_services')->insert($insert);
                }
            }

            DB::commit();

            $pkg->load(['foods', 'services', 'hall', 'restaurant']);
            return response()->json($pkg);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Gói gợi ý không tìm thấy'], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi cập nhật gói gợi ý', 'error' => $e->getMessage()], 500);
        }
    }

    // DELETE /suggestion-packages/{id}
    public function destroy($id)
    {
        try {
            $pkg = SuggestionPackage::findOrFail($id);

            DB::beginTransaction();
            // detach relations (if pivot tables exist)
            if (method_exists($pkg, 'foods')) $pkg->foods()->detach();
            if (method_exists($pkg, 'services')) $pkg->services()->detach();

            $pkg->delete();
            DB::commit();

            return response()->json(['message' => 'Đã xóa gói gợi ý']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Gói gợi ý không tìm thấy'], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi xóa gói gợi ý', 'error' => $e->getMessage()], 500);
        }
    }

    //
    // Convenience endpoints to add/remove single food/service from a package
    // POST  /suggestion-packages/{id}/foods    body: { food_id: 123 }
    // DELETE /suggestion-packages/{id}/foods/{foodId}
    // (and similarly for services)
    //

    public function addFood(Request $request, $id)
    {
        $data = $request->validate([
            'food_id' => 'required|integer|exists:foods,id',
        ]);

        try {
            $pkg = SuggestionPackage::findOrFail($id);
            $pkg->foods()->syncWithoutDetaching([$data['food_id']]);

            $pkg->load('foods');
            return response()->json($pkg->foods);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Gói gợi ý không tìm thấy'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi khi thêm món vào gói', 'error' => $e->getMessage()], 500);
        }
    }

    public function removeFood($id, $foodId)
    {
        try {
            $pkg = SuggestionPackage::findOrFail($id);
            $pkg->foods()->detach($foodId);

            return response()->json(['message' => 'Đã xóa món khỏi gói']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Gói gợi ý không tìm thấy'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi khi xóa món khỏi gói', 'error' => $e->getMessage()], 500);
        }
    }

    public function addService(Request $request, $id)
    {
        $data = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
        ]);

        try {
            $pkg = SuggestionPackage::findOrFail($id);
            $pkg->services()->syncWithoutDetaching([$data['service_id']]);

            $pkg->load('services');
            return response()->json($pkg->services);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Gói gợi ý không tìm thấy'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi khi thêm dịch vụ vào gói', 'error' => $e->getMessage()], 500);
        }
    }

    public function removeService($id, $serviceId)
    {
        try {
            $pkg = SuggestionPackage::findOrFail($id);
            $pkg->services()->detach($serviceId);

            return response()->json(['message' => 'Đã xóa dịch vụ khỏi gói']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Gói gợi ý không tìm thấy'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi khi xóa dịch vụ khỏi gói', 'error' => $e->getMessage()], 500);
        }
    }
}
