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
        Schema::create('continuous_runs', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['running', 'stopped'])->default('running');
            $table->integer('total_cycles')->default(0);
            $table->integer('total_statements')->default(0);
            $table->integer('total_errors')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('continuous_runs');
    }
};
