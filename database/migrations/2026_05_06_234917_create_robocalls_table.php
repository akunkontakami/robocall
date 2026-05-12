<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('robocalls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('robocall_name');
            $table->string('route')->nullable();
            $table->string('ivr')->nullable();
            $table->enum('data_type', ['campaign', 'upload'])->nullable();
            $table->uuid('marketing_campaign_id')->nullable();

            $table->tinyInteger('is_running');

            $table->integer('call_limit')->nullable();
            $table->json('status_campaigns')->nullable();

            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robocalls');
    }
};
