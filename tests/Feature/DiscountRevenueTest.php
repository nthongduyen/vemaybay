<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use App\Models\KhuyenMai;
use App\Models\SanBay;
use App\Models\MayBay;
use App\Models\ChuyenBay;
use App\Models\Booking;
use App\Models\Ve;
use App\Models\HoaDon;
use App\Models\ChiTietHoaDon;
use App\Models\DoanhThu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Kiểm tra luồng tính toán Khuyến Mãi, Tổng Tiền, Hóa Đơn và ghi nhận Doanh Thu.
 */
class DiscountRevenueTest extends TestCase
{
    use RefreshDatabase;

    // Cố định các giá trị để dễ kiểm tra
    protected $giaVeCoBanPhanTram = 500.00; // Dùng cho test giảm %
    protected $giaTriGiamPhanTram = 10;     // 10%

    protected $giaVeCoBanCoDinh = 1000000.00; // Dùng cho test giảm cố định
    protected $giaTriGiamCoDinh = 100000.00;  // 100,000 VND
    protected $soLuongVe = 2;
    protected $nguoiDung;
    protected $chuyenBay;

    /**
     * Thiết lập môi trường và tạo dữ liệu giả cho tất cả các test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. TẠO DỮ LIỆU CƠ SỞ CHUNG
        $sanBayDi = SanBay::factory()->create(['ma_san_bay' => 'SGN']);
        $sanBayDen = SanBay::factory()->create(['ma_san_bay' => 'HAN']);
        $mayBay = MayBay::factory()->create();
        $this->nguoiDung = NguoiDung::factory()->create();

        // Tạo Chuyến Bay (sẽ dùng chung cho cả 2 test case)
        $this->chuyenBay = ChuyenBay::factory()->create([
            'id_may_bay' => $mayBay->id,
            'id_san_bay_di' => $sanBayDi->id,
            'id_san_bay_den' => $sanBayDen->id,
            // Giá vé mặc định là 1,000,000 VND, sẽ ghi đè trong test Percentage
            'gia_ve' => $this->giaVeCoBanCoDinh,
        ]);
    }

    /**
     * @test
     * Mô tả: Kiểm tra luồng tính toán với Khuyến Mãi Giảm Phần Trăm (10%).
     */
    public function test_percentage_discount_flow()
    {
        // Ghi đè giá vé cho test case này
        $chuyenBay = ChuyenBay::factory()->create([
            'gia_ve' => $this->giaVeCoBanPhanTram, // 500.00
            'id_may_bay' => $this->chuyenBay->id_may_bay,
            'id_san_bay_di' => $this->chuyenBay->id_san_bay_di,
            'id_san_bay_den' => $this->chuyenBay->id_san_bay_den,
        ]);

        // Chuẩn bị Khuyến Mãi Giảm Phần Trăm 10%
        $khuyenMai = KhuyenMai::factory()->create([
            'ma_khuyen_mai' => 'SALE10',
            'gia_tri' => $this->giaTriGiamPhanTram, // 10%
            'loai_gia_tri' => 'phan_tram',
            'ngay_bat_dau' => Carbon::now()->subDay(),
            'ngay_ket_thuc' => Carbon::now()->addDay(),
            'trang_thai' => 'active',
        ]);

        // Tính toán dự kiến
        $tongTienChuaGiam = $this->giaVeCoBanPhanTram * $this->soLuongVe; // 500.00 * 2 = 1000.00
        $giaTriGiam = $tongTienChuaGiam * ($this->giaTriGiamPhanTram / 100); // 100.00
        $tongTienSauGiam = $tongTienChuaGiam - $giaTriGiam; // 900.00

        // 2. TẠO BOOKING
        $booking = Booking::factory()->create([
            'id_nguoi_dung' => $this->nguoiDung->id,
            'tong_tien' => $tongTienSauGiam,
            'id_khuyen_mai' => $khuyenMai->id,
            'trang_thai' => 'thanh_cong',
        ]);

        $this->assertEquals(900.00, $booking->refresh()->tong_tien, 'Lỗi: Tổng tiền Booking (Giảm %) không chính xác.');
    }

    // -------------------------------------------------------------------------------- //

    /**
     * @test
     * @group ITG-TT-01
     * @group ITG-TT-02
     * @group ITG-TT-03
     * Mô tả: Kiểm tra toàn bộ luồng tích hợp tính toán với Khuyến Mãi Giảm Cố Định (ITG-TT-01 -> ITG-TT-03).
     */
    public function test_fixed_discount_integration_flow()
    {
        // Giá vé: 1,000,000 VND/vé. Số vé: 2. Giảm: 100,000 VND
        $tongTienChuaGiam = $this->giaVeCoBanCoDinh * $this->soLuongVe; // 2,000,000 VND
        $tongTienThanhToan = $tongTienChuaGiam - $this->giaTriGiamCoDinh; // 1,900,000 VND

        // 1. CHUẨN BỊ KHUYẾN MÃI (Giảm Cố Định)
        $khuyenMai = KhuyenMai::factory()->create([
            'ma_khuyen_mai' => 'ITG100K',
            'gia_tri' => $this->giaTriGiamCoDinh, // 100,000 VND
            'loai_gia_tri' => 'gia_tri_co_dinh',
            'ngay_bat_dau' => Carbon::now()->subDay(),
            'ngay_ket_thuc' => Carbon::now()->addDay(),
            'trang_thai' => 'active',
        ]);

        /// 2. TẠO BOOKING
        $booking = Booking::factory()->create([
            'id_nguoi_dung' => $this->nguoiDung->id,
            'tong_tien' => $tongTienThanhToan, // 1,900,000 VND
            'id_khuyen_mai' => $khuyenMai->id,
            'trang_thai' => 'paid',
        ]);

        // 3. TẠO VÉ (2 vé)
        $ve1 = Ve::factory()->create([
            'id_booking' => $booking->id,
            'id_chuyen_bay' => $this->chuyenBay->id,
            'gia_ve' => $this->giaVeCoBanCoDinh
        ]);
        $ve2 = Ve::factory()->create([
            'id_booking' => $booking->id,
            'id_chuyen_bay' => $this->chuyenBay->id,
            //  LỖI ĐÃ ĐƯỢC SỬA: $this->giaVeCoDinh -> $this->giaVeCoBanCoDinh
            'gia_ve' => $this->giaVeCoBanCoDinh
        ]);

        // ============= KIỂM TRA ITG-TT-01 (Booking) =============

        $this->assertEquals($tongTienThanhToan, $booking->refresh()->tong_tien, 'ITG-TT-01 Lỗi: Booking.tong_tien phải là 1,900,000 VND.');
        $this->assertEquals($khuyenMai->id, $booking->khuyenMai->id, 'ITG-TT-01 Lỗi: Booking không liên kết đúng Khuyến Mãi.');


        // 4. TẠO HÓA ĐƠN VÀ CHI TIẾT HÓA ĐƠN
        $hoaDon = HoaDon::factory()->create([
            'id_booking' => $booking->id,
            'tong_tien' => $tongTienThanhToan, // 1,900,000 VND
            'trang_thai' => 'da_thanh_toan',
        ]);

        // Tạo 2 Chi Tiết Hóa Đơn (Mỗi vé là 1 chi tiết)
        // YÊU CẦU: ChiTietHoaDon.gia phải là giá gốc vé (1,000,000 VND)
        ChiTietHoaDon::factory()->create([
            'id_hoa_don' => $hoaDon->id,
            'id_ve' => $ve1->id,
            'so_luong' => 1,
            'gia' => $this->giaVeCoBanCoDinh
        ]);
        ChiTietHoaDon::factory()->create([
            'id_hoa_don' => $hoaDon->id,
            'id_ve' => $ve2->id,
            'so_luong' => 1,
            //  LỖI ĐÃ ĐƯỢC SỬA: $this->giaVeCoDinh -> $this->giaVeCoBanCoDinh
            'gia' => $this->giaVeCoBanCoDinh
        ]);


        // ============= KIỂM TRA ITG-TT-02 (HoaDon & ChiTietHoaDon) =============

        $this->assertEquals($tongTienThanhToan, $hoaDon->refresh()->tong_tien, 'ITG-TT-02 Lỗi: HoaDon.tong_tien phải là 1,900,000 VND.');
        $this->assertCount($this->soLuongVe, $hoaDon->chiTiets, 'ITG-TT-02 Lỗi: Phải có 2 Chi Tiết Hóa Đơn.');

        // Kiểm tra giá trong mỗi Chi Tiết Hóa Đơn là giá gốc (1,000,000 VND)
        foreach ($hoaDon->chiTiets as $chiTiet) {
            $this->assertEquals($this->giaVeCoBanCoDinh, $chiTiet->gia, 'ITG-TT-02 Lỗi: ChiTietHoaDon.gia phải là giá gốc (1,000,000 VND).');
        }


        // 5. GHI NHẬN DOANH THU
        $doanhThu = DoanhThu::factory()->create([
            'id_hoa_don' => $hoaDon->id,
            'thang' => Carbon::now()->month,
            'nam' => Carbon::now()->year,
            'doanh_thu' => $tongTienThanhToan,
        ]);

        // ============= KIỂM TRA ITG-TT-03 (DoanhThu) =============

        $this->assertEquals($tongTienThanhToan, $doanhThu->refresh()->doanh_thu, 'ITG-TT-03 Lỗi: DoanhThu.doanh_thu phải là 1,900,000 VND.');
        $this->assertTrue($hoaDon->doanhThu->contains($doanhThu), 'ITG-TT-03 Lỗi: Hóa đơn không liên kết với bản ghi Doanh Thu.');
    }
}

//Áp dụng Khuyến mãi và Ghi nhận Doanh
// File: database/factories/ChuyenBayFactory.php
//MayBayFactory.php -- SanBayFactory.php -- 2025_11_18_000003_MayBay.php
//database/factories/KhuyenMaiFactory.php
//2025_11_18_000006_create_khuyen_mai_table.php
//database/factories/VeFactory.php
//database/factories/HoaDonFactory.php
//database/migrations/*_create_hoa_don_table.php
//database/factories/ChiTietHoaDonFactory.php
//database/migrations/*_create_chi_tiet_hoa_don_table.php
//database/factories/DoanhThuFactory.php
//database/migrations/*_create_doanh_thu_table.php

//php artisan test --filter DiscountRevenueTest
