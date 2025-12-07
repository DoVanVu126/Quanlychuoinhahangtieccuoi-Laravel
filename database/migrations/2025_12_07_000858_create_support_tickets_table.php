<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('support_tickets', function (Blueprint $table) {
        // LƯU Ý: Khóa chính ở đây tên là 'ticket_id'
        $table->id('ticket_id'); 
        
        $table->unsignedBigInteger('user_id'); 
        
        $table->string('customer_name');
        $table->string('customer_email');
        $table->string('customer_phone')->nullable();
        
        $table->string('subject');
        $table->text('message');
        
        $table->string('priority')->default('low'); // low, medium, high, urgent
        $table->string('status')->default('new');   // new, in_progress, resolved, closed
        
        $table->timestamps();
        
        // Khóa ngoại liên kết User
        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('support_tickets');
}
};
