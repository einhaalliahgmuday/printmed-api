<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->foreignId('patient_id')->constrained('patients', 'id');
            $table->string('patient_name');
            $table->integer('amount');
            $table->string('method');
            $table->string('hmo')->nullable();
            $table->string('status')->default('Not yet paid');
            $table->foreignId('physician_id')->constrained('users', 'id');
            $table->string('physician_name');
            $table->string('department');
            $table->integer('update_count')->nullable();
            $table->foreignId('updated_by_id')->nullable()->constrained('users', 'id');
            $table->string('updated_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
