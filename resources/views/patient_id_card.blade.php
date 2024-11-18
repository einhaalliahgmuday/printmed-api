<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient ID Card</title>
    <style>
		@page {
			size: 8.5in 11in;
			margin: 1in;
		}
		
		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}
		
		body {
			font-family: Calibri;
			font-size: 10px;
			line-height: 1;
		}
		
        .id-card-container {
			position: relative;
            width: 3.375in;
            height: 2.125in;
            border: 1px solid #000;
			border-radius: 7px;
			overflow: hidden;
        }

		.id-card-content {
			position: absolute;
			padding: 7px 0;
			width: 100%;
			height: 100%;
        }

		.bg {
			position: absolute;
			width: 100%;
			height: 100%;
			opacity: 75%;
			border-radius: 7px;
		}
		
		.logo {
			max-width: 100%;
			height: 25px;
			display: block;
			margin: auto;
		}
		
		.title-container {
			background-color: #B43C3A; 
			color: white; 
			font-weight: bold; 
			padding: 5px 0; 
			font-size: 10px;
			text-align: center;
			margin: 7px 0;
		}
		
		.information-container {
			margin: 0 7px;
			height: 1.3in;
			padding: 1px;
			background-color: #FFF;
			border-radius: 7px;
		}

		.information-container > table {
			width: 100%;
			height: 100%;
			border-spacing: 7px;
			word-wrap: break-word;
 		 	word-break: break-word;
		}

		.information-container td {
			width: 33.3%;
			height: 100%;
		}

		.information-container td > div {
			height: 100%;
		}

		.id-photo-container {
			height: 1in;
			width: 1in;
			max-width: 100%;
		}
		
		.id-photo {
			height: 100%;
			width: 100%;
			border-radius: 5px;
			object-fit: cover;
		}

		.valid-until-text {
			text-align: center;
			font-size: 8px;
			margin-top: 5px;
			font-weight: bold;
			font-style: italic;
		}

		.information-title {
			font-size: 8px;
		}

		.information {
			font-weight: bold;
			text-transform: uppercase;
		}

		.mb-2 {
			margin-bottom: 2px;
		}

		.qr-container {
			margin-top: 6px;
			height: .68in;
			width: .68in;
		}

		.qr {
			height: 100%;
			width: 100%;
			object-fit: cover;
		}

		.information-container.back div {
			text-align: center;
			margin: 18px auto;
		}

		.w-80 {
			width: 75%;
		}

		.w-95 {
			width: 95%;
		}

		.information-container span {
			font-weight: bold;
		}

		.fs-8 {
			font-size: 8px;
		}

		.fi {
			font-style: italic;
		}
    </style>
</head>
<body>
	{{-- <table>
		<tr>
			<td> --}}
				<div class="id-card-container">
					<img class="bg" src="{{ public_path('images/patient_id_card_bg.png') }}" alt="">
					
					<div class="id-card-content">
						<div>
							<img class="logo" src="{{ public_path('images/carmona_hospital_logo_1.png') }}" alt="">
						</div>
						<div class="title-container">
							<p>PATIENT IDENTIFICATION CARD</p>
						</div>
						<div class="information-container">
							<table>
								<tr>
									<td>
										<div>
											<div class="id-photo-container">
												<img class="id-photo" src="{{ storage_path('app/private/images/patients/REQLTcBlb62TxW8AjVJRL1mOaYey8KhlJdAYAAcO.png') }}" alt="">
											</div>
											<p class="valid-until-text">Valid Until: August 9, 2021</p>
										</div>
									</td>
									<td>
										<div>
											<div class="mb-2">
												<p class="information-title">Patient Number</p>
												<p class="information">{{"$patient->patient_number"}}</p>
											</div>
											<div class="mb-2">
												<p class="information-title">Name</p>
												<p class="information">{{"$patient->full_name"}}</p>
											</div>
											<div>
												<p class="information-title">Address</p>
												<p class="information fs-8">Qx3Tq5nH7b8LrA2FpV9yZzJwK1G</p>
											</div>
										</div>
									</td>
									<td>
										<div>
											<div class="mb-2">
												<p class="information-title">Birthdate</p>
												<p class="information">{{"$patient->birthdate"}}</p>
											</div>
											<div class="mb-2">
												<p class="information-title">Sex</p>
												<p class="information">{{"$patient->sex"}}</p>
											</div>
											<div class="qr-container">
												<img class="qr" src="{{ 'data:image/png;base64,' . $qr }}" alt="">
											</div>
										</div>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</td>
			<td>
				<div class="id-card-container">
					<img class="bg" src="{{ public_path('images/patient_id_card_bg.png') }}" alt="">
					
					<div class="id-card-content">
						<div>
							<img class="logo" src="{{ public_path('images/carmona_hospital_logo_1.png') }}" alt="">
						</div>
						<div class="title-container">
							<p>IN CASE OF LOSS</p>
						</div>
						<div class="information-container back">
							<div class="w-80">
								<p>If this ID is lost or stolen, please immediately report it to the hospital's registration desk or contact our support team to deactivate and reissue your ID.</p>
								<p><span>Phone: </span>{{"(02) 1234-5678"}}</p>
								<p><span>Email: </span>{{"support@carmonamedical.com"}}</p>
							</div>
							<div class="w-95">
								<p class="fs-8 fi">This QR code is used exclusively for accessing the patient's record within the Patient Management Record System. It serves as an identification method to ensure secure and authorized access to the patient’s health information. Unauthorized use of this QR code is prohibited and may result in legal action.</p>
							</div>
						</div>
					</div>
				</div>
			{{-- </td>
		</tr>
	</table> --}}
</body>
</html>