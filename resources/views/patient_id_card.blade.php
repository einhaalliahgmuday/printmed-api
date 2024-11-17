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
			font-family: Arial;
			font-size: 8px;
		}
		
        .id-card {
			position: relative;
            width: 3.375in;
            height: 2.125in;
            border: 1px solid #000;
            display: flex;
            flex-direction: column;
			align-items: center;
			padding: 7px 0;
        }
		
		.id-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-image: "{{public_path('assets/images/patient_id_card_bg.png')}}";
			background-size: cover;
			background-position: center;
			background-repeat: no-repeat;
			opacity: 0.75;
			z-index: -1;  
        }
		
		.logo {
			max-width: 100%;
			height: 25px;
		}
		
		.title-card {
			background-color: #B43C3A; 
			color: white; 
			width: 100%; 
			font-weight: bold; 
			margin: 7px 0; 
			font-size: 10px;
		}
		
		.information-card {
			margin: 0 7px;
			height: 100%;
			padding: 7px;
			background-color: #FFF;
			border-radius: 7px;
		}
		
		.information-card.front {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			grid-gap: 6px;
		}
		
		.information-card.back {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: space-between;
			text-align: center;
		}
		
		.photo-container {
			width: 1in;
			height: 1in;
		}
		
		.qr-container {
			width: .7;
			height: .7in;
		}
		
		.photo, .qr {
			max-width: 100%;
			max-height: 100%;
			border-radius: 2.5px;
			object-fit: cover;
		}
    </style>
</head>
<body">
	{{-- style="background-image: {{$photo}}; height: 500px; --}}
    {{-- <img {{'data:image/png;base64,'.base64_encode($qr)}} width="100px"/> --}}
	<div style="height: 300px; width: 300px; background-image: url('file:///C:/path/to/your/project/public/storage/images/patient_id_card_bg.png')"></div>
	 {{-- <p>{{$photo}}</p> --}}
    {{-- <img src="{{$photo}}" class="logo" style="height: 2.125in; width: 3.375in;"> --}}
     <!-- <img src="" alt=""> -->
</body>
</html>