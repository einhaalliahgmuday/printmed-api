<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Restriction Notice</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 1.5rem;
                text-align: center;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: #fff;
                padding: 1.5rem 2.5rem;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            .logo-container {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .logo {
                max-width: 100%;
                height: 50px;
                margin-bottom: .5rem;
                margin: 0 auto;
            }
            .bold {
                font-weight: bold;
            }
            .italic {
                font-style: italic;
            }
            .h-8 {
                height: 8px;
            }
            li {
                text-align: start;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo-container">
                <img class="logo" src="https://firebasestorage.googleapis.com/v0/b/souschef-2024.firebasestorage.app/o/carmona_hospital_logo_1.png?alt=media&token=b6b2a182-f2a8-4000-9b0d-ce3b050a26c4" alt="">
            </div>
            <h3>Dear {{ "$name" }},</h3>
            <p>We noticed multiple failed login attempts on your account, which has resulted in a temporary restriction for security purposes. This measure helps to protect your account from unauthorized access.</p>
            <p class="bold">Next Steps:</p>
            <ul>
                <li>Your account will automatically be unlocked in <strong>an hour</strong>.</li>
                <li>If you have forgotten your password or suspect unauthorized activity, please <a href="http://localhost:5173/reset-password">reset your password</a>.</li>
                <li>If you believe this was an error, or if you need further assistance, please contact our support team immediately at <a href="mailto:printmed.samsantech@gmail.com">printmed.samsantech@gmail.com</a>.</li>
            </ul>
            <p>Thank you for your understanding. We apologize for any inconvenience this may cause.</p>
            <div class="h-8"></div>
            <p><span class="bold">Best regards,</span><br>Carmona Hospital and Medical Center Support Team</p>
        </div>
    </body>
</html>