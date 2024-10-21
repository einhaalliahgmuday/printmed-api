<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('consultation_records', function (Blueprint $table) {
            $table->id();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('blood_pressure', 7)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->text('chief_complaint');
            $table->text('history_of_present_illness')->nullable();
            $table->text('family_hx')->nullable();
            $table->text('medical_hx')->nullable();
            $table->text('pediatrics_h')->nullable();
            $table->text('pediatrics_e')->nullable();
            $table->text('pediatrics_a')->nullable();
            $table->text('pediatrics_d')->nullable();
            $table->text('primary_diagnosis')->nullable();
            $table->text('diagnosis');
            $table->text('prescription')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->foreignId('patient_id')->constrained('patients', 'id')->cascadeOnDelete();
            $table->foreignId('physician_id')->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_records');
    }
};
