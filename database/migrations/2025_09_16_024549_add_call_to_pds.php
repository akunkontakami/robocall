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
        Schema::table('pds', function (Blueprint $table) {
            $table->integer("call_factor")->nullable();
            $table->integer("call_wait")->nullable();
            $table->integer("call_abandon_rate")->nullable();
            $table->integer("call_limit")->nullable();
            $table->integer("call_retry_after")->nullable();
            $table->integer("call_retry_max")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pds', function (Blueprint $table) {
            $table->dropColumn("call_factor");
            $table->dropColumn("call_wait");
            $table->dropColumn("call_abandon_rate");
            $table->dropColumn("call_limit");
            $table->dropColumn("call_retry_after");
            $table->dropColumn("call_retry_max");
        });
    }
};
