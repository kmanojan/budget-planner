<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset OTP</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .logo { text-align: center; margin-bottom: 30px; font-size: 24px; font-weight: bold; color: #6C5CE7; }
        h2 { color: #2d3436; text-align: center; margin-bottom: 10px; }
        p { color: #636e72; line-height: 1.6; text-align: center; }
        .otp-box { background: #f8f9fa; border: 2px dashed #6C5CE7; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #6C5CE7; letter-spacing: 8px; }
        .expiry { font-size: 13px; color: #b2bec3; margin-top: 20px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #b2bec3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">💰 Budget Planner</div>
        <h2>Password Reset</h2>
        <p>You requested to reset your password. Use the OTP code below to proceed:</p>

        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <p class="expiry">This code will expire in <strong>5 minutes</strong>.</p>
        <p>If you didn't request this, please ignore this email.</p>

        <div class="footer">
            &copy; {{ date('Y') }} Budget Planner. All rights reserved.
        </div>
    </div>
</body>
</html>
