<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Your Prescription from Latest Consultation at Carmona Hospital and Medical Center</title>
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
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo-container">
                <img class="logo" src="https://firebasestorage.googleapis.com/v0/b/souschef-2024.firebasestorage.app/o/carmona_hospital_logo_1.png?alt=media&token=b6b2a182-f2a8-4000-9b0d-ce3b050a26c4" alt="">
            </div>
            <h3>Hi, {{ "$patient_first_name" }}.</h3>
            <p>We hope you are doing well.</p>
            <p>As part of your recent visit to Carmona Hospital and Medical Center, we have prepared your <span class="bold italic">prescription</span>. Please find the prescription attached to this email.</p>
            <p>Thank you for choosing Carmona Hospital and Medical Center for your healthcare needs. We wish you all the best in your treatment and recovery.</p>
            <div class="h-8"></div>
            <p><span class="bold">Best regards,</span><br>Carmona Hospital and Medical Center</p>
        </div>
    </body>
</html>