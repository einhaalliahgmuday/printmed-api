<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Header</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .logo {
            max-width: 100%;
            height: 50px;
            display: inline-block;
            margin: 0 16px;
        }
        .header-line {
            height: 8px;
            width: 540px;
            margin-top: -45px;
            display: inline-block;
            background-color: #6CB6AD;
            vertical-align: middle;
        }
        .margin {
            height: 16px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-line"></div>
        <img class="logo" src="{{ public_path('images/carmona_hospital_logo_1.png') }}" alt="">
        <div class="header-line"></div>
        <div class="margin"></div>
    </header>
</body>
</html>