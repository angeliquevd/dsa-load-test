<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('statement_responses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('statement_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->timestamp('response_created_at');
            $table->timestamps();
        });
    }
};
