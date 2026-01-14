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
        Schema::table('continuous_runs', function (Blueprint $table) {
            $table->integer('total_single_cycles')->default(0)->after('total_errors');
            $table->integer('total_single_statements')->default(0)->after('total_single_cycles');
            $table->integer('total_single_errors')->default(0)->after('total_single_statements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('continuous_runs', function (Blueprint $table) {
            $table->dropColumn(['total_single_cycles', 'total_single_statements', 'total_single_errors']);
        });
    }
};
