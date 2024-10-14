<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physician_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('physician_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients', 'id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physician_patients');
    }
};
