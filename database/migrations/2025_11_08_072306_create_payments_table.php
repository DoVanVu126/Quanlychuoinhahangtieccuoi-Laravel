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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('booking_id');
            $table->decimal('total_amount', 18, 2);
            $table->decimal('deposit_amount', 18, 2)->nullable();
            $table->decimal('remaining_amount', 18, 2)->nullable();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid']);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'e-wallet']);
            $table->string('transaction_code', 100)->nullable();
            $table->dateTime('payment_date')->useCurrent();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('booking_id')
                  ->references('booking_id')
                  ->on('bookings')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
