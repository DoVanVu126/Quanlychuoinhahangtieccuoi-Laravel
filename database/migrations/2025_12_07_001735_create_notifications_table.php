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
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id'); // Người nhận thông báo

        // --- CÁC CỘT LIÊN KẾT (FOREIGN KEYS) ---
        $table->unsignedBigInteger('booking_id')->nullable();
        $table->unsignedBigInteger('promotion_id')->nullable();
        
        // Thêm cột support_ticket_id (cho phép null)
        $table->unsignedBigInteger('support_ticket_id')->nullable(); 
        
        // Cột generic (dùng chung nếu cần)
        $table->unsignedBigInteger('related_id')->nullable()->comment('ID đối tượng liên quan');

        // --- NỘI DUNG ---
        $table->string('title');
        $table->text('message');
        
        // Cột Type
        $table->string('type')->default('system')->comment('system, support_reply, order, info...');
        
        $table->boolean('is_read')->default(false);
        $table->timestamps();

        // --- RÀNG BUỘC KHÓA NGOẠI ---
        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
        $table->foreign('promotion_id')->references('promotion_id')->on('promotions')->onDelete('cascade');
        
        // QUAN TRỌNG: Sửa references('id') thành references('ticket_id')
        // Vì bảng support_tickets của bạn dùng ticket_id làm khóa chính
        $table->foreign('support_ticket_id')
              ->references('ticket_id') // <--- Chỗ này phải khớp với bên bảng support_tickets
              ->on('support_tickets')
              ->onDelete('cascade');
    });
}

public function down(): void
{
    Schema::dropIfExists('notifications');
}
};
