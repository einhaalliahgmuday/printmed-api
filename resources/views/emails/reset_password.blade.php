<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 1.5rem;
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
        .button {
			display: block;
			width: fit-content;
            text-decoration: none;
			color: #fff;
            padding: .7rem 1.4rem;
			margin: 1rem 0;
            background-color: #007bff;
            border-radius: 5px;
        }
		.note {
			margin-bottom: .5rem;
		}
        .ii a[href] {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img class="logo" src="https://firebasestorage.googleapis.com/v0/b/souschef-2024.firebasestorage.app/o/carmona_hospital_logo_1.png?alt=media&token=b6b2a182-f2a8-4000-9b0d-ce3b050a26c4" alt="">
        </div>
        @if (!$isNewAccount)
            <p>We received a request to reset your password.</p>
            <p>Use the link below to set up a new password for your account.</p>
        @else
            <p>Your account has been successfully created. To get started, please reset your password using the link below.</p>
            <p>Once you've set your password, you’ll be able to log in.</p>
        @endif
        <a href="{{ $url }}" class="button">Reset your password</a>
		<p class="note">The link will expire in 24 hours. If you did not expect this email, please disregard it.</p>
    </div>
</body>
</html>
