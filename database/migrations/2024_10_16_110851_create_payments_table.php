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
            $table->integer('amount');
            $table->string('method');
            $table->string('hmo')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->foreignId('updated_by_id')->nullable()->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('consultation_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients', 'id')->onDelete('restrict');
            $table->foreignId('physician_id')->constrained('users', 'id')->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
