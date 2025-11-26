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
        if (!Schema::hasTable('suggestion_services')) {
            Schema::create('suggestion_services', function (Blueprint $table) {
                $table->id('suggestion_service_id');
                $table->unsignedBigInteger('package_id');
                $table->unsignedBigInteger('service_id');
                $table->timestamps();

                $table->foreign('package_id')->references('package_id')->on('suggestion_packages')->onDelete('cascade');
                $table->foreign('service_id')->references('service_id')->on('services')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_services');
    }
};
