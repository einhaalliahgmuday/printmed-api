<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Prescription</title>
    <style>
		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
            word-wrap: break-word;
            overflow: break-word;
		}
		body {
			font-family: Calibri;
			font-size: 16px;
			line-height: 1;
            background-color: white;
		}
        .container {
            /* border-right: {{ $pdf == true ? '2px solid black' : '0px solid transparent' }}; */
            /* border-bottom: 2px solid black; */
            width: 5.5in;
            height: 9in;
            /* padding: 16px 20px 20px; */
        }
        .logo {
            height: 60px;
            max-width: 100%;
            margin: auto;
            display: block;
        }
        .rx {
            height: 60px;
            max-width: 100%;
        }
        .bg-container {
            position: absolute;
            top: 35px;
            left: 0;
            bottom: 0;
            right: 0;
            z-index: -1;
        }
        .bg {
            max-width: 100%;
            max-height: 360px;
            display: block;
            margin: auto;
        }
        .prescriptions-container {
            position: relative;
            height: 410px;
        }
        .signature {
            max-width: 100%;
            height: 30px;
        }
        .bold {
            font-weight: bold;
        }
        .text-md {
            font-size: 18px;
        }
        .text-lg {
            font-size: 20px;
        }
        .text-xl {
            font-size: 24px;
        }
        .text-center {
            text-align: center;
        }
        .mb-20 {
            margin-bottom: 20px
        }
        .underline {
            text-decoration: underline;
        }
        .inline-block {
            display: inline-block;
        }
        .mr-10 {
            margin-right: 10px;
        }
        .mr-30 {
            margin-right: 30px;
        }
        .mt-15 {
            margin-top: 15px; 
        }
        .mt-20 {
            margin-top: 20px;
        } 
        .mt-10 {
            margin-top: 10px;
        }
        .mb-8 {
            margin-bottom: 8px;
        }   
        .mb-15 {
            margin-bottom: 15px;
        }   
        .bb-black {
            border-bottom: 1px solid black;
        }
        .px-10 {
            padding: 0 10px;
        }
        .pl-5 {
            padding-left: 5px;
        }
    </style>
</head>
<body>
    @foreach ($prescriptions as $prescription)
        <div class="container inline-block">
            <div>
                <img class="logo" src="{{ public_path('images/carmona_hospital_logo_4.png') }}" alt="">
            </div>
            <p class="bold text-lg text-center">Carmona Hospital and Medical Center</p>
            <p class="text-center mb-20">Cabilang Baybay, Carmona Cavite, Philippines</p>
            <p class="bold text-lg text-center">OUTPATIENT DEPARTMENT</p>
            <p class="text-center mb-20 text-lg bold">Pediatrics</p>
            <div class="mb-20">
                <div class="mb-8">
                    <p class="inline-block mr-10">Date</p>
                    <div class="inline-block bb-black mr-10 pl-5" style="width: 150px;">{{$date->format('F j, Y')}}</div>
                    <p class="inline-block mr-10">Patient Number</p>
                    <div class="inline-block bb-black pl-5" style="width: 155px;">{{$patient->patient_number}}</div>
                </div>   
                <div class="mb-8">
                    <p class="inline-block mr-10">Name of Patient</p>
                    <div class="inline-block bb-black pl-5" style="width: 360px;">{{$patient->full_name}}</div>
                </div>  
                <div class="mb-8">   
                    <p class="inline-block mr-10">Age</p>
                    <div class="inline-block bb-black mr-10 pl-5" style="width: 50px;">{{$patient->age}}</div>
                    <p class="inline-block mr-10">Birthdate</p>
                    <div class="inline-block bb-black mr-10 pl-5" style="width: 140px;">{{Carbon\Carbon::parse($patient->birthdate)->format('F j, Y')}}</div>
                    <p class="inline-block mr-10">Gender</p>
                    <div class="inline-block bb-black pl-5" style="width: 100px;">{{$patient->sex}}</div>
                </div>   
                <div>
                    <p class="inline-block mr-10">Address</p>
                    <div class="inline-block bb-black pl-5" style="width: 414px;">{{$patient->address}}</div>
                </div>
            </div>
            <div class="prescriptions-container">
                <div class="bg-container">
                    <img class="bg" src="{{ public_path('images/carmona_hospital_logo_transparent_2.png') }}" alt="">
                </div>
                <div>
                    <img class="rx inline-block" src="{{ public_path('images/rx.png') }}" alt="">
                </div>
                <div class="text-md">
                    @foreach ($prescription as $item)
                        <div class="mt-15 text-lg">
                            <p class="bold"><span class="mr-10">{{$item['name']}}</span>{{$item['dosage']}}</p>
                            <p>{{$item['instruction']}}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="mb-8">
                    <p class="inline-block mr-10">PTR #</p>
                    <div class="inline-block bb-black pl-5" style="width: 422px;">{{$ptr}}</div>
                </div>  
                <div class="mb-8">
                    <p class="inline-block mr-10">S2 #</p>
                    <div class="inline-block bb-black pl-5" style="width: 432px;">{{$s2}}</div>
                </div>  
                <div class="mb-8">
                    <p class="inline-block mr-10">Follow-up Date</p>
                    <div class="inline-block bb-black pl-5" style="width: 362px;">
                        @if ($follow_up_date)
                            {{Carbon\Carbon::parse($follow_up_date)->format('F j, Y')}}
                        @endif
                    </div>
                </div>  
                <div>
                    <p class="inline-block mr-10">Physician</p>
                    <div class="inline-block bb-black pl-5" style="width: 400px;">{{$physician->full_name}}, MD</div>
                </div>  
                <div class="mt-10 mb-8">
                    <p class="inline-block mr-10">Signature of Physician</p>
                    <div class="inline-block bb-black pl-5" style="width: 319px;">
                        @if ($signature != null)
                            <img class="signature" src="{{ 'data:image/png;base64,' . $signature }}" alt="">
                        @endif
                    </div>
                </div>  
            </div>
        </div>
    @endforeach
</body>
</html>