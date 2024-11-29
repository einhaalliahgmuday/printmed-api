<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_physician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients', 'id')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments', 'id')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_physicians');
    }
};
