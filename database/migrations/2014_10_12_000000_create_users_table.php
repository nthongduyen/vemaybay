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
        // QUAN TRỌNG: Đổi tên bảng từ 'users' thành 'nguoi_dung'
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->id();

            // Cột tên: Đổi từ 'name' thành 'ho_ten' (theo Model NguoiDung)
            $table->string('ho_ten');

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Cột mật khẩu: Đổi từ 'password' thành 'mat_khau'
            $table->string('mat_khau');

            // Thêm các cột bắt buộc khác trong Model NguoiDung
            $table->string('so_dien_thoai')->nullable();
            $table->string('dia_chi')->nullable();
            $table->string('vai_tro')->default('khach_hang');
            $table->integer('trang_thai')->default(1);

            $table->rememberToken();

            // Timestamps: Chỉ dùng 'ngay_tao' và bỏ 'updated_at' (theo Model NguoiDung)
            $table->timestamp('ngay_tao')->useCurrent();
            // $table->timestamps(); // Bỏ dòng này
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // QUAN TRỌNG: Đổi tên bảng thành 'nguoi_dung'
        Schema::dropIfExists('nguoi_dung');
    }
};
