<?php

namespace Tests\Feature;

use App\Models\ChuyenBay;
use App\Models\NguoiDung;
use App\Models\Booking;
use App\Models\Ve;
use App\Models\ThongTinNguoiDi;
use App\Models\SanBay;
use App\Models\MayBay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Kiểm tra các mối quan hệ (ORM) giữa NguoiDung, Booking, Ve và ThongTinNguoiDi.
 */
class BookingRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected NguoiDung $khachHang;
    protected ChuyenBay $chuyenBay;
    protected float $giaVe = 1500000.00;

    /**
     * Thiết lập môi trường và tạo dữ liệu giả cần thiết.
     * Điều kiện: Đã tạo 1 NguoiDung và 1 ChuyenBay.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. TẠO DỮ LIỆU CƠ SỞ (SanBay, MayBay)
        $sanBayDi = SanBay::create(['ma_san_bay' => 'SGN', 'ten_san_bay' => 'Tân Sơn Nhất', 'thanh_pho' => 'TP.HCM', 'quoc_gia' => 'VN']);
        $sanBayDen = SanBay::create(['ma_san_bay' => 'HAN', 'ten_san_bay' => 'Nội Bài', 'thanh_pho' => 'Hà Nội', 'quoc_gia' => 'VN']);
        $mayBay = MayBay::create(['ma_may_bay' => 'MB-001', 'ten_may_bay' => 'Boeing 787', 'hang_san_xuat' => 'Boeing', 'so_ghe' => 300, 'trang_thai' => 'hoat_dong']);

        // 2. TẠO CHUYẾN BAY (Đã đảm bảo mã 10 ký tự)
        $thoiGianDi = Carbon::now()->addDays(7);
        $this->chuyenBay = ChuyenBay::create([
            'ma_chuyen_bay' => 'SGN-HAN-01', // Tối đa 10 ký tự
            'id_may_bay' => $mayBay->id,
            'id_san_bay_di' => $sanBayDi->id,
            'id_san_bay_den' => $sanBayDen->id,
            'thoi_gian_di' => $thoiGianDi,
            'thoi_gian_den' => $thoiGianDi->copy()->addHours(2),
            'gia_ve' => $this->giaVe,
            'trang_thai' => 'dang_ban',
        ]);

        // 3. TẠO NGƯỜI DÙNG ĐẶT VÉ
        $this->khachHang = NguoiDung::create([
            'ho_ten' => 'Nguyễn Văn Khách',
            'email' => 'khachhang@test.com',
            'mat_khau' => bcrypt('password123'),
            'so_dien_thoai' => '0901234567',
            'vai_tro' => 'khach_hang',
        ]);
    }

    // -----------------------------------------------------

    /**
     * @test
     * ID: ITG-DV-01
     * Mô tả: Kiểm tra mối quan hệ Booking (hasMany) Vé (belongsTo).
     */
    public function testBookingHasManyVeAndVeBelongsToBookingRelationship()
    {
        // 1. Tạo Booking (2 vé) cho NguoiDung
        $booking = Booking::create([
            'id_nguoi_dung' => $this->khachHang->id,
            'ma_booking' => 'BK-ITG-01',
            'tong_tien' => $this->giaVe * 2,
            'trang_thai' => 'dat_thanh_cong',
            'phuong_thuc_tt' => 'chuyen_khoan',
        ]);

        // 2. Tạo 2 Vé liên kết với Booking và ChuyenBay
        $ve1 = Ve::create([
            'id_booking' => $booking->id,
            'id_chuyen_bay' => $this->chuyenBay->id,
            'so_ghe' => '10A',
            'gia_ve' => $this->giaVe,
            'trang_thai' => 'da_xuat',
        ]);
        $ve2 = Ve::create([
            'id_booking' => $booking->id,
            'id_chuyen_bay' => $this->chuyenBay->id,
            'so_ghe' => '10B',
            'gia_ve' => $this->giaVe,
            'trang_thai' => 'da_xuat',
        ]);

        // KẾT QUẢ MONG ĐỢI 1: Booking được tạo thành công
        $this->assertDatabaseHas('booking', ['id' => $booking->id]);

        // KẾT QUẢ MONG ĐỢI 2: Truy vấn Booking->ves() trả về Collection chứa đúng 2 đối tượng Vé.
        $this->assertCount(2, $booking->ves, 'Lỗi: Booking->ves() phải trả về 2 vé.');

        // Kiểm tra ngược lại (Ve belongsTo Booking)
        $this->assertEquals($booking->id, $ve1->booking->id, 'Lỗi: Ve->booking() không liên kết đúng Booking.');
    }

    // -----------------------------------------------------

    /**
     * @test
     * ID: ITG-DV-02
     * Mô tả: Kiểm tra mối quan hệ Vé (hasOne) Thông Tin Người Đi (belongsTo).
     */
    public function testVeHasOneThongTinNguoiDiRelationship()
    {
        // Điều kiện: Đã tạo Vé ITG-DV-01. (Ta tạo lại một vé mới để test độc lập)
        $booking = Booking::create(['id_nguoi_dung' => $this->khachHang->id, 'ma_booking' => 'BK-ITG-02', 'tong_tien' => $this->giaVe, 'trang_thai' => 'dat_thanh_cong', 'phuong_thuc_tt' => 'the_tin_dung']);

        $ve = Ve::create([
            'id_booking' => $booking->id,
            'id_chuyen_bay' => $this->chuyenBay->id,
            'so_ghe' => '11C',
            'gia_ve' => $this->giaVe,
            'trang_thai' => 'da_xuat',
        ]);

        // 1. Tạo ThongTinNguoiDi liên kết với Vé
        $ttnd = ThongTinNguoiDi::create([
            'id_ve' => $ve->id,
            'ho_ten' => 'Lê Văn Hành Khách',
            'so_dien_thoai' => '0912345678',
            'email' => 'hanhkhach@test.com',
            'dia_chi' => '123 ABC',
        ]);

        // 2. Truy vấn Ve->thongTinNguoiDi()
        $veReloaded = $ve->fresh(); // Lấy lại model để đảm bảo quan hệ được tải
        $ttndFetchedByVe = $veReloaded->thongTinNguoiDi;

        // KẾT QUẢ MONG ĐỢI 1: Ve->thongTinNguoiDi() trả về đối tượng TTND duy nhất.
        $this->assertNotNull($ttndFetchedByVe, 'Lỗi: Ve->thongTinNguoiDi() không trả về Thông tin người đi.');
        $this->assertInstanceOf(ThongTinNguoiDi::class, $ttndFetchedByVe, 'Lỗi: Đối tượng trả về không phải là ThongTinNguoiDi.');
        $this->assertEquals($ttnd->id, $ttndFetchedByVe->id, 'Lỗi: ID Thông tin người đi không khớp.');

        // 3. Truy vấn ThongTinNguoiDi->ve()
        $veFetchedByTTND = $ttnd->ve;

        // KẾT QUẢ MONG ĐỢI 2: ThongTinNguoiDi->ve() trả về đối tượng Vé đã liên kết.
        $this->assertNotNull($veFetchedByTTND, 'Lỗi: ThongTinNguoiDi->ve() không trả về Vé.');
        $this->assertInstanceOf(Ve::class, $veFetchedByTTND, 'Lỗi: Đối tượng trả về không phải là Vé.');
        $this->assertEquals($ve->id, $veFetchedByTTND->id, 'Lỗi: ID Vé không khớp.');
    }

    // -----------------------------------------------------

    /**
     * @test
     * ID: ITG-DV-03
     * Mô tả: Kiểm tra NguoiDung có thể truy xuất các Booking của họ (hasMany).
     */
    public function testNguoiDungHasManyBookingsRelationship()
    {
        // 1. Tạo Booking 1
        $booking1 = Booking::create([
            'id_nguoi_dung' => $this->khachHang->id,
            'ma_booking' => 'BK-USER-01',
            'tong_tien' => $this->giaVe * 1,
            'trang_thai' => 'dat_thanh_cong',
            'phuong_thuc_tt' => 'chuyen_khoan',
        ]);

        // 2. Tạo Booking 2 (Đã có điều kiện ITG-DV-01 tạo booking)
        // Tạo thêm một booking thứ hai để kiểm tra quan hệ hasMany
        $booking2 = Booking::create([
            'id_nguoi_dung' => $this->khachHang->id,
            'ma_booking' => 'BK-USER-02',
            'tong_tien' => $this->giaVe * 3,
            'trang_thai' => 'dat_thanh_cong',
            'phuong_thuc_tt' => 'tien_mat',
        ]);

        // 1. Truy vấn NguoiDung->bookings()
        $bookings = $this->khachHang->bookings;

        // KẾT QUẢ MONG ĐỢI: NguoiDung->bookings() trả về Collection chứa Booking vừa tạo (2 bookings).
        $this->assertCount(2, $bookings, 'Lỗi: NguoiDung->bookings() phải trả về 2 booking.');
        $this->assertTrue($bookings->contains($booking1), 'Lỗi: Booking 1 không có trong danh sách.');
        $this->assertTrue($bookings->contains($booking2), 'Lỗi: Booking 2 không có trong danh sách.');

        // Kiểm tra ngược lại (Booking belongsTo NguoiDung)
        $this->assertEquals($this->khachHang->id, $booking1->nguoiDung->id, 'Lỗi: Booking->nguoiDung() không liên kết đúng người dùng.');
    }
}

//php artisan test --filter BookingRelationshipTest

//2025_11_19_000005_create_khuyen_mai_table.php  --  2025_11_19_000006_create_booking_table.php
// 2025_11_19_000007_create_ve_table.php-- 2025_11_19_000008_create_thong_tin_nguoi_di_table.php
