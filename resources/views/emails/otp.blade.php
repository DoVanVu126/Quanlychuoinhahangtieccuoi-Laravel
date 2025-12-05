<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .otp-box {
            background-color: #f4f4f4;
            border: 2px dashed #007bff;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
        }
        .warning {
            color: #dc3545;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Lấy Lại Mật Khẩu</h2>
        <p>Bạn đã yêu cầu lấy lại mật khẩu. Vui lòng sử dụng mã OTP dưới đây:</p>
        
        <div class="otp-box">
            <div class="otp-code">{{ $otpCode }}</div>
        </div>
        
        <p>Mã OTP này sẽ <strong>hết hạn sau {{ $expiresInMinutes }} phút</strong>.</p>
        
        <p class="warning">⚠️ Nếu bạn không yêu cầu lấy lại mật khẩu, vui lòng bỏ qua email này.</p>
        
        <p>Trân trọng,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>