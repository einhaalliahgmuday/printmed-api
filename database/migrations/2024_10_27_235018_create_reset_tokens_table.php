<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token');
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reset_tokens');
    }
};
