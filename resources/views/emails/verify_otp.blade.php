<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Let's log you in</title>
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
        .code {
            font-size: 1.5rem;
            letter-spacing: .5rem;
        }
		.note {
			margin-bottom: .5rem;
		}
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img class="logo" src="https://firebasestorage.googleapis.com/v0/b/souschef-2024.firebasestorage.app/o/carmona_hospital_logo_1.png?alt=media&token=b6b2a182-f2a8-4000-9b0d-ce3b050a26c4" alt="">
        </div>
        @if ($isVerifyEmail)
            <h3>Verify Your Email</h3>
            <p>Use this code to update your email at PrintMed.</p>
        @else
            <h3>Let's log you in</h3>
            <p>Use this code to sign up to PrintMed.</p>
        @endif
        <p class="code">{{$code}}</p>
		<p class="note">The code will expire in 5 minutes. If you did not expect this email, please disregard it.</p>
    </div>
</body>
</html>