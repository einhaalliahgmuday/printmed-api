<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify to Login</title>
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
            padding: 1.5rem;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Let's log you in</h2>
        <p>Here is your code to sign-up: <strong>{{ $code }}</strong></p>
        <p>This will expire in 10 minutes.</p>
    </div>
</body>
</html>
