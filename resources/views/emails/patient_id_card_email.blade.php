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
                height: 60px;
                margin-bottom: .5rem;
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
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo-container">
                <img src="{{asset('images/carmona_hospital_logo_1.png')}}" class="logo">
            </div>
            <h3>Hi, {{ "$patient_first_name" }}.</h3>
            <p>Thank you for choosing Carmona Hospital and Medical Center for your healthcare needs.</p>
            <p>We are pleased to provide you with your <span class="bold italic">digital identification card</span>. Please find it attached in this email, and you may present it during your upcoming visits in our Outpatient Department.</p>
            <div class="h-8"></div>
            <p><span class="bold">Best regards,</span><br>Carmona Hospital and Medical Center</p>
        </div>
    </body>
</html>