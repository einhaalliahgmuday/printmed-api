<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_number');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 50)->nullable();
            $table->date('birthday')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('civil_status', 20)->nullable();
            $table->string('religion')->nullable();
            $table->string('phone_number', 40)->nullable();
            $table->timestamps();
        });

        // DB::statement('ALTER TABLE patient_number AUTO_INCREMENT = 1000;');
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
