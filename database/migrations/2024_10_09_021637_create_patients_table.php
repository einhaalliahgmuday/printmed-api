<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number');
            $table->string('full_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('birthdate')->nullable();
            $table->text('birthplace')->nullable();
            $table->string('sex')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            // $table->string('barangay_code')->nullable();
            $table->string('city')->nullable();
            $table->string('city_code')->nullable();
            $table->string('province')->nullable();
            $table->string('province_code')->nullable();
            $table->string('region')->nullable();
            $table->string('region_code')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('religion')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('hmo')->nullable();
            $table->string('rekognitionFaceId')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
