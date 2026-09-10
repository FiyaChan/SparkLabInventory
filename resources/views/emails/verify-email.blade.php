<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>
<body style="background-color: #F8FAFC; margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #EDE9FE; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <!-- Header -->
        <tr>
            <td align="center" style="padding: 36px 24px 20px; background: linear-gradient(135deg, #4C1D95 0%, #7E22CE 100%);">
                <table border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <div style="width: 52px; height: 52px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; line-height: 52px; font-size: 26px; text-align: center; color: #ffffff; display: inline-block; margin-bottom: 12px;">
                                🧪
                            </div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.02em;">{{ $appName }}</h1>
                            <p style="color: #E9D5FF; margin: 6px 0 0; font-size: 13px;">Security & Account Verification</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding: 36px 32px 28px; color: #1E293B;">
                <h2 style="color: #1E1B4B; font-size: 20px; font-weight: 700; margin: 0 0 16px;">Hello {{ $user->name }},</h2>
                <p style="font-size: 15px; line-height: 1.6; color: #475569; margin: 0 0 20px;">
                    Thank you for joining <strong>{{ $appName }}</strong>! To finalize your account setup and ensure the security of your explorer account, please click the button below to verify your email address.
                </p>

                <!-- Action Button -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0 32px;">
                    <tr>
                        <td align="center">
                            <a href="{{ $verificationUrl }}" target="_blank" style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 600; color: #ffffff; background: #7E22CE; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(126, 34, 206, 0.35); text-align: center;">
                                Verify Email Address &rarr;
                            </a>
                        </td>
                    </tr>
                </table>

                <div style="background-color: #FAF5FF; border-left: 4px solid #9333EA; padding: 14px 16px; border-radius: 4px; margin-bottom: 24px;">
                    <p style="font-size: 13px; line-height: 1.5; color: #581C87; margin: 0;">
                        ⏱️ <strong>Link Expiration:</strong> This verification link will automatically expire in <strong>{{ $expireMinutes }} minutes</strong>. If you did not create an account, no further action is required.
                    </p>
                </div>

                <p style="font-size: 14px; line-height: 1.5; color: #64748B; margin: 0 0 12px;">
                    Having trouble with the button? Copy and paste this URL directly into your web browser:
                </p>
                <p style="font-size: 12px; line-height: 1.4; color: #7E22CE; word-break: break-all; margin: 0 0 24px; padding: 10px; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px;">
                    {{ $verificationUrl }}
                </p>

                <p style="font-size: 14px; line-height: 1.5; color: #475569; margin: 0;">
                    Warm regards,<br>
                    <strong>The {{ $appName }} Team</strong>
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 24px 32px; background-color: #F1F5F9; border-top: 1px solid #E2E8F0; text-align: center; color: #94A3B8; font-size: 12px;">
                <p style="margin: 0 0 6px;">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                <p style="margin: 0; color: #64748B;">This is an automated system email. Please do not reply directly to this message.</p>
            </td>
        </tr>
    </table>
</body>
</html>
