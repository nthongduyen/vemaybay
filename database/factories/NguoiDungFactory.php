<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NguoiDungFactory extends Factory
{
    /**
     * Tên Model tương ứng với Factory này.
     * @var string
     */
    protected $model = NguoiDung::class;

    /**
     * Định nghĩa các thuộc tính mặc định của Model NguoiDung.
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // SỬA ĐỔI: Dùng 'ho_ten' thay vì 'name'
            'ho_ten' => $this->faker->name(),

            'email' => $this->faker->unique()->safeEmail(),

            // SỬA ĐỔI: Dùng 'mat_khau' thay vì 'password'
            // Mật khẩu hash của chuỗi 'password'
            'mat_khau' => Hash::make('password'),

            'so_dien_thoai' => $this->faker->phoneNumber(),
            'dia_chi' => $this->faker->address(),

            // Đặt vai trò mặc định để test đăng nhập, ví dụ 'khach_hang'
            'vai_tro' => 'khach_hang',
            'trang_thai' => 1, // Kích hoạt

            // Sử dụng cột 'ngay_tao' đã định nghĩa trong Model
            'ngay_tao' => now(),

            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }
}
