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
            $table->string('height')->nullable();
            $table->int('height_unit')->nullable();
            $table->string('weight')->nullable();
            $table->int('weight_unit')->nullable();
            $table->string('temperature')->nullable();
            $table->int('temperature_unit')->nullable();
            $table->string('blood_pressure')->nullable();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
