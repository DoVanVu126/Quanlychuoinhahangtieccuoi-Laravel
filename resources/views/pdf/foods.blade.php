<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        h2 {
            text-align: center;
        }

        img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h2>Danh sách món ăn</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên</th>
                <th>Nhà hàng</th>
                <th>Loại món</th>
                <th>Đơn vị</th>
                <th>Giá</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($foods as $food)
            <tr>
                <td>{{ $food->food_id }}</td>

                <td>
                    @if ($food->image_url)
                    <img src="{{ public_path('uploads/foods/' . $food->image_url) }}" alt="Ảnh món ăn">
                    @else
                    -
                    @endif
                </td>

                <td>{{ $food->name }}</td>
                <td>{{ $food->restaurant->name ?? '-' }}</td>
                <td>{{ $food->foodType->name ?? '-' }}</td>
                <td>{{ $food->unit }}</td>
                <td>{{ number_format($food->price, 0, ',', '.') }} đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
