<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('height')->nullable();
            $table->int('height_unit')->nullable();
            $table->string('weight')->nullable();
            $table->int('weight_unit')->nullable();
            $table->string('temperature')->nullable();
            $table->int('temperature_unit')->nullable();
            $table->string('blood_pressure')->nullable();
            $table->longText('chief_complaint');
            $table->longText('present_illness_hx')->nullable();
            $table->longText('family_hx')->nullable();
            $table->longText('medical_hx')->nullable();
            $table->longText('pediatrics_h')->nullable();
            $table->longText('pediatrics_e')->nullable();
            $table->longText('pediatrics_a')->nullable();
            $table->longText('pediatrics_d')->nullable();
            $table->longText('primary_diagnosis')->nullable();
            $table->longText('diagnosis');
            $table->string('follow_up_date')->nullable();
            $table->foreignId('patient_id')->constrained('patients', 'id')->cascadeOnDelete();
            $table->foreignId('physician_id')->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
