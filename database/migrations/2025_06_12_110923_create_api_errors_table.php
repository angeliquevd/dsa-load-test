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
        Schema::create('api_errors', function (Blueprint $table) {
            $table->id();
            $table->string('url')->nullable();
            $table->string('method');
            $table->longText('request_payload')->nullable();
            $table->integer('statement_response_id')->nullable();
            $table->integer('status_code')->nullable();
            $table->longText('error_message')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_errors');
    }
};
