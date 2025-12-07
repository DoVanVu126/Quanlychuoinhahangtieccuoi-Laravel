<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán VNPAY</title>
</head>
<body>
    <h2>Thanh toán qua VNPAY</h2>
    <form id="payment-form">
        <label>Số tiền (VND):</label>
        <input type="number" name="amount" required>
        <button type="submit">Thanh toán</button>
    </form>
    <div id="result"></div>
    <script>
        document.getElementById('payment-form').onsubmit = async function(e) {
            e.preventDefault();
            const amount = this.amount.value;
            const res = await fetch('/payment', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({amount})
            });
            const data = await res.json();
            if (data.payment_url) {
                window.location.href = data.payment_url;
            } else {
                document.getElementById('result').innerText = 'Có lỗi xảy ra!';
            }
        }
    </script>
</body>
</html>
