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
        Schema::create('robocall_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('robocall_id');
            $table->string('customer_id')->nullable();
            $table->uuid('robocall_file_id')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('robocall_id')
                ->references('id')
                ->on('robocalls')
                ->onDelete('cascade');

            $table->foreign('robocall_file_id')
                ->references('id')
                ->on('robocall_files')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robocall_customers');
    }
};
