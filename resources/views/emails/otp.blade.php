<!DOCTYPE html>
<html>
<head>
    <title>Sajha Auction - OTP Verification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
    <div style="max-w-md mx-auto bg-white p-8 border border-gray-200 rounded-lg shadow-sm">
        <h2 style="color: #1f2937; margin-bottom: 20px;">Hello {{ $name }},</h2>
        <p style="color: #4b5563; font-size: 16px;">Here is your verification code for Sajha Auction. Please enter it to complete your login:</p>
        
        <div style="background-color: #eff6ff; padding: 15px; text-align: center; border-radius: 8px; margin: 25px 0;">
            <span style="font-size: 32px; font-weight: bold; color: #2563eb; letter-spacing: 5px;">{{ $code }}</span>
        </div>

        <p style="color: #6b7280; font-size: 14px;">This code will expire in 10 minutes.</p>
        <p style="color: #6b7280; font-size: 14px;">If you did not request this code, you can safely ignore this email.</p>
        
        <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="color: #9ca3af; font-size: 12px; text-align: center;">&copy; {{ date('Y') }} Sajha Auction. All rights reserved.</p>
    </div>
</body>
</html>
