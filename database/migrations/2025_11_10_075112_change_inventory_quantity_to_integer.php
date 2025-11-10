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
        Schema::table('inventory', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
            $table->integer('reorder_level')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->decimal('quantity', 18, 2)->default(0)->change();
            $table->decimal('reorder_level', 18, 2)->default(0)->change();
        });
    }
};
