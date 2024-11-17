<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->int('height');
            $table->int('height_unit');
            $table->int('weight');
            $table->int('weight_unit');
            $table->int('temperature');
            $table->int('temperature_unit');
            $table->string('blood_pressure');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
