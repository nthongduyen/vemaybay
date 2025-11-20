<?php

namespace Tests\Feature;

use App\Models\ChuyenBay;
use App\Models\NguoiDung;
use App\Models\SanBay;
use App\Models\MayBay;
use Illuminate\Foundation\Testing\DatabaseTransactions; // <--- DÙNG DÒNG NÀY
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuanLyChuyenBayTest extends TestCase
{

    use DatabaseTransactions;

    protected $admin;
    protected $sanBayDi;
    protected $sanBayDen;
    protected $mayBay;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tạo dữ liệu giả lập (Các dữ liệu này sẽ tự mất sau khi test xong)

        // Tạo Admin
        $this->admin = NguoiDung::create([
            'ho_ten' => 'Admin Test',
            'email' => 'admintest' . rand(1000,9999) . '@test.com', // Email ngẫu nhiên để tránh trùng
            'mat_khau' => Hash::make('123456'),
            'vai_tro' => 'admin', // QUAN TRỌNG: Đảm bảo vai trò là 'admin'
            // Lưu ý: Nếu DB của bạn cột này là số (INT), hãy để là 1. Nếu là Enum, để 'hoat_dong'.
            // Dựa trên lỗi cũ của bạn, tôi để là 1 (active).
            'trang_thai' => 1,
        ]);

        // Kiểm tra xem Sân Bay đã có chưa, nếu chưa thì tạo mới để lấy ID
        $this->sanBayDi = SanBay::firstOrCreate(
            ['ma_san_bay' => 'SGN'],
            ['ten_san_bay' => 'Tan Son Nhat', 'dia_chi' => 'HCM', 'quoc_gia' => 'VN', 'tinh_thanh' => 'HCM']
        );

        $this->sanBayDen = SanBay::firstOrCreate(
            ['ma_san_bay' => 'HAN'],
            ['ten_san_bay' => 'Noi Bai', 'dia_chi' => 'Ha Noi', 'quoc_gia' => 'VN', 'tinh_thanh' => 'Ha Noi']
        );

        $this->mayBay = MayBay::firstOrCreate(
            ['ma_may_bay' => 'VN-TEST'],
            [
                'ten_may_bay' => 'Boeing 787',
                'so_ghe' => 100,
                // SỬA TẠI ĐÂY: Dùng một giá trị có trong ENUM
                'trang_thai' => 'hoat_dong', // HOẶC 'active'
                'hang_san_xuat' => 'Boeing'
            ]
        );
    }

    /**
     * Test Case IT05: Thêm chuyến bay mới
     */
    public function test_it05_admin_co_the_them_chuyen_bay()
    {
        $maChuyenBay = 'VN' . rand(1000, 9999); // Mã ngẫu nhiên

        $duLieuChuyenBay = [
            'ma_chuyen_bay'  => $maChuyenBay,
            'id_may_bay'     => $this->mayBay->id,
            'id_san_bay_di'  => $this->sanBayDi->id,
            'id_san_bay_den' => $this->sanBayDen->id,
            'thoi_gian_di'   => '2025-12-20 08:00:00',
            'thoi_gian_den'  => '2025-12-20 10:00:00',
            'gia_ve'         => 1500000,
            'trang_thai'     => 'dang_ban',
             // THÊM CÁC TRƯỜNG DƯỚI ĐÂY NẾU CONTROLLER CẦN
             '_token' => csrf_token(),
        ];

        // Giả lập Admin đăng nhập và POST dữ liệu
        // Lưu ý: Sửa lại đường dẫn '/admin/chuyen-bay' đúng với route trong web.php của bạn
        $response = $this->actingAs($this->admin)
                             ->post('/admin/chuyen-bay', $duLieuChuyenBay);

        // Kiểm tra kết quả
        // 302: Redirect (thường là thành công), 200: OK
        $status = $response->status();
        $this->assertTrue(in_array($status, [200, 201, 302]), "Lỗi: Status code trả về là $status");

        // Kiểm tra Database có dữ liệu mới không
        $this->assertDatabaseHas('chuyen_bay', [
            'ma_chuyen_bay' => $maChuyenBay,
            'gia_ve'        => 1500000,
        ]);
    }

    /**
     * Test Case IT06: Sửa thông tin chuyến bay
     */
    public function test_it06_admin_co_the_sua_chuyen_bay()
    {
        // Tạo một chuyến bay nháp để sửa
        $chuyenBayCu = ChuyenBay::create([
            'ma_chuyen_bay'  => 'VN-OLD-' . rand(100,999),
            'id_may_bay'     => $this->mayBay->id,
            'id_san_bay_di'  => $this->sanBayDi->id,
            'id_san_bay_den' => $this->sanBayDen->id,
            'thoi_gian_di'   => '2025-12-01 08:00:00',
            'thoi_gian_den'  => '2025-12-01 10:00:00',
            'gia_ve'         => 1000000,
            'trang_thai'     => 'dang_ban'
        ]);

        // Dữ liệu mới
        $maMoi = 'VN-NEW-' . rand(100,999);
        $duLieuMoi = [
            'ma_chuyen_bay'  => $maMoi,
            'id_may_bay'     => $this->mayBay->id,
            'id_san_bay_di'  => $this->sanBayDi->id,
            'id_san_bay_den' => $this->sanBayDen->id,
            'thoi_gian_di'   => '2025-12-01 08:00:00',
            'thoi_gian_den'  => '2025-12-01 10:00:00',
            'gia_ve'         => 2000000,
            'trang_thai'     => 'dang_ban',
            // THÊM DÒNG NÀY VÌ PHƯƠNG THỨC PUT/PATCH THƯỜNG CẦN NÓ
            '_token' => csrf_token(),
            '_method' => 'PUT' // Dòng này là bắt buộc nếu bạn dùng form HTML thông thường
        ];

        // Gửi request Update
        // Lưu ý: Sửa lại đường dẫn '/admin/chuyen-bay/{id}' đúng với route của bạn
        $response = $this->actingAs($this->admin)
                             ->put("/admin/chuyen-bay/{$chuyenBayCu->id}", $duLieuMoi);

        // Kiểm tra DB: Giá vé đã đổi từ 1tr -> 2tr chưa
        $this->assertDatabaseHas('chuyen_bay', [
            'id' => $chuyenBayCu->id,
            'ma_chuyen_bay' => $maMoi,
            'gia_ve' => 2000000
        ]);
    }

    /**
     * Test Case IT07: Xóa chuyến bay
     */
    public function test_it07_admin_co_the_xoa_chuyen_bay()
    {
        // CHỈNH SỬA: Tạo chuyến bay mới với ID cao hơn để tránh xung đột
        $chuyenBay = ChuyenBay::create([
            'ma_chuyen_bay'  => 'VN-DEL-' . rand(100,999),
            'id_may_bay'     => $this->mayBay->id,
            'id_san_bay_di'  => $this->sanBayDi->id,
            'id_san_bay_den' => $this->sanBayDen->id,
            'thoi_gian_di'   => '2025-12-01 08:00:00',
            'thoi_gian_den'  => '2025-12-01 10:00:00',
            'gia_ve'         => 500000,
            'trang_thai'     => 'dang_ban'
        ]);

        // Gửi request Delete
        $response = $this->actingAs($this->admin)
                             ->delete("/admin/chuyen-bay/{$chuyenBay->id}", [
                                // THÊM DÒNG NÀY ĐỂ LARAVEL NHẬN DIỆN REQUEST LÀ DELETE
                                '_token' => csrf_token(),
                             ]);

        // Kiểm tra DB: Không còn thấy chuyến bay này nữa
        // Lỗi này xảy ra khi Khóa ngoại chưa được xử lý, tôi đã sửa Controller để trả về Redirect (302)
        // và giả định rằng không có Vé nào trỏ đến Chuyến Bay này trong Test.
        $this->assertDatabaseMissing('chuyen_bay', [
            'id' => $chuyenBay->id,
        ]);
    }
}
// routes/web.php // chuyenbaycontroller


//php artisan test --filter=QuanLyChuyenBayTest
