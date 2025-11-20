<?php

namespace Tests\Feature;

use App\Models\SanBay;
use App\Models\MayBay;
use App\Models\Ghe;
use App\Models\ChuyenBay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kiểm tra các mối quan hệ và tính toàn vẹn dữ liệu
 * của Chuyến Bay, Máy Bay, Ghế và Sân Bay.
 */
class FlightInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $sanBayDi;
    protected $sanBayDen;
    protected $mayBay;
    protected $chuyenBay;
    protected $soLuongGhe = 5;

    /**
     * Thiết lập môi trường và tạo dữ liệu giả cho tất cả các test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. TẠO SÂN BAY (SanBay.php)
        // LƯU Ý: Đã đổi 'tinh_thanh' thành 'thanh_pho' để khớp với file migration
        $this->sanBayDi = SanBay::create([
            'ma_san_bay' => 'SGN',
            'ten_san_bay' => 'Sân bay Tân Sơn Nhất',
            'quoc_gia' => 'Việt Nam',
            'thanh_pho' => 'TP. Hồ Chí Minh', // ĐÃ SỬA LỖI: Dùng 'thanh_pho'
            'dia_chi' => 'Đường Trường Sơn'
        ]);

        $this->sanBayDen = SanBay::create([
            'ma_san_bay' => 'HAN',
            'ten_san_bay' => 'Sân bay Nội Bài',
            'quoc_gia' => 'Việt Nam',
            'thanh_pho' => 'Hà Nội', // ĐÃ SỬA LỖI: Dùng 'thanh_pho'
            'dia_chi' => 'Huyện Sóc Sơn'
        ]);


// ...
        // 2. TẠO MÁY BAY (MayBay.php)
        $this->mayBay = MayBay::create([
            'ma_may_bay' => 'A320-VN',
            'ten_may_bay' => 'Airbus A320',
            // ✅ ĐÃ THÊM DÒNG NÀY ĐỂ KHẮC PHỤC LỖI
            'hang_san_xuat' => 'Airbus',
            'so_ghe' => $this->soLuongGhe,
            'trang_thai' => 'active',
        ]);
// ...
        /// 3. TẠO CHUYẾN BAY (Cần thiết cho test đầu tiên)
        // Bạn cần đảm bảo bạn có tạo $this->chuyenBay ở đây (hoặc dùng Factory)
        // Ví dụ:
        $this->chuyenBay = ChuyenBay::create([
            'ma_chuyen_bay' => 'VN123',
            'id_may_bay' => $this->mayBay->id,
            'id_san_bay_di' => $this->sanBayDi->id,
            'id_san_bay_den' => $this->sanBayDen->id,
            'thoi_gian_di' => now()->addDay(),
            'thoi_gian_den' => now()->addDay()->addHours(2),
            'gia_ve' => 150.00,
            'trang_thai' => 'dang_ban',
        ]);


        /// 4. TẠO GHẾ (MỚI: KHẮC PHỤC LỖI HIỆN TẠI)
        // Cần tạo TỔNG CỘNG 5 ghế để khớp với $this->soLuongGhe = 5
        Ghe::create([
            'id_may_bay' => $this->mayBay->id,
            'so_ghe' => 'A1',
            'loai_ghe' => 'Business',
            'trang_thai' => 'available'
        ]);
        Ghe::create([
            'id_may_bay' => $this->mayBay->id,
            'so_ghe' => 'A2',
            'loai_ghe' => 'Business',
            'trang_thai' => 'available'
        ]);
        Ghe::create([
            'id_may_bay' => $this->mayBay->id,
            'so_ghe' => 'B1',
            'loai_ghe' => 'Economy',
            'trang_thai' => 'available'
        ]);

        // 🚀 THÊM 2 GHẾ NÀY VÀO ĐỂ TỔNG SỐ LƯỢNG LÀ 5
        Ghe::create([
            'id_may_bay' => $this->mayBay->id,
            'so_ghe' => 'B2',
            'loai_ghe' => 'Economy',
            'trang_thai' => 'available'
        ]);
        Ghe::create([
            'id_may_bay' => $this->mayBay->id,
            'so_ghe' => 'C1',
            'loai_ghe' => 'Economy',
            'trang_thai' => 'available'
        ]);

        // Hoặc thay thế 5 dòng trên bằng cách dùng Factory và tạo 5 đối tượng nếu bạn có `GheFactory`
        // Ghe::factory()->count($this->soLuongGhe)->for($this->mayBay)->create();
    }


    /**
     * @test
     * ID: ITG-CB-01
     * Mô tả: Kiểm tra ChuyenBay liên kết đúng Máy Bay, Sân Bay Đi, Sân Bay Đến (belongsTo).
     */
    public function testChuyenBayBelongsToRelationships()
    {
        // ... (Code kiểm tra giữ nguyên)
        $this->assertNotNull($this->chuyenBay);

        // 1. Kiểm tra ChuyenBay -> MayBay
        $mayBayCuaCB = $this->chuyenBay->mayBay;
        $this->assertEquals($this->mayBay->id, $mayBayCuaCB->id, 'Lỗi: Chuyến bay không liên kết đúng Máy Bay.');
        $this->assertEquals('A320-VN', $mayBayCuaCB->ma_may_bay, 'Lỗi: Mã Máy Bay không khớp.');

        // 2. Kiểm tra ChuyenBay -> SanBayDi
        $sanBayDiCuaCB = $this->chuyenBay->sanBayDi;
        $this->assertEquals($this->sanBayDi->id, $sanBayDiCuaCB->id, 'Lỗi: Chuyến bay không liên kết đúng Sân Bay Đi.');
        $this->assertEquals('SGN', $sanBayDiCuaCB->ma_san_bay, 'Lỗi: Mã Sân Bay Đi phải khớp.');

        // 3. Kiểm tra ChuyenBay -> SanBayDen
        $sanBayDenCuaCB = $this->chuyenBay->sanBayDen;
        $this->assertEquals($this->sanBayDen->id, $sanBayDenCuaCB->id, 'Lỗi: Chuyến bay không liên kết đúng Sân Bay Đến.');
        $this->assertEquals('HAN', $sanBayDenCuaCB->ma_san_bay, 'Lỗi: Mã Sân Bay Đến không khớp.');
    }


    /**
     * @test
     * ID: ITG-CB-02
     * Mô tả: Kiểm tra mối quan hệ 1-N giữa Máy Bay và Ghế.
     */
    public function testMayBayGheHasManyRelationship()
    {
        // ... (Code kiểm tra giữ nguyên)
        // 1. Kiểm tra MayBay -> Ghes (hasMany)
        $ghesCuaMayBay = $this->mayBay->ghes;
        $this->assertCount($this->soLuongGhe, $ghesCuaMayBay, 'Lỗi: Số lượng ghế phải bằng số lượng đã tạo (5).');

        // Kiểm tra phân loại ghế
        $countBusiness = $ghesCuaMayBay->where('loai_ghe', 'Business')->count();
        $this->assertEquals(2, $countBusiness, 'Lỗi: Phải có 2 ghế Business.');

        // 2. Kiểm tra Ghe -> MayBay (belongsTo)
        $gheDauTien = $ghesCuaMayBay->first();
        $mayBayCuaGhe = $gheDauTien->mayBay;

        $this->assertEquals($this->mayBay->id, $mayBayCuaGhe->id, 'Lỗi: Ghế không liên kết ngược lại đúng Máy Bay.');
    }

    /**
     * @test
     * ID: ITG-CB-03
     * Mô tả: Kiểm tra Sân Bay có thể truy xuất các Chuyến Bay đi/đến (hasMany).
     */
    public function testSanBayChuyenBayHasManyRelationship()
    {
        // ... (Code kiểm tra giữ nguyên)
        // 1. Kiểm tra SanBayDi -> ChuyenBayDi
        $chuyenBayDiSGN = $this->sanBayDi->chuyenBayDi;
        $this->assertCount(1, $chuyenBayDiSGN, 'Lỗi: Sân Bay SGN phải có 1 chuyến bay đi.');
        $this->assertEquals($this->chuyenBay->id, $chuyenBayDiSGN->first()->id, 'Lỗi: ID chuyến bay đi không khớp.');

        // 2. Kiểm tra SanBayDen -> ChuyenBayDen
        $chuyenBayDenHAN = $this->sanBayDen->chuyenBayDen;
        $this->assertCount(1, $chuyenBayDenHAN, 'Lỗi: Sân Bay HAN phải có 1 chuyến bay đến.');
        $this->assertEquals($this->chuyenBay->id, $chuyenBayDenHAN->first()->id, 'Lỗi: ID chuyến bay đến không khớp.');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $chuyenBayDiSGN);
    }
}
// lệnh chạy: php artisan test --filter=FlightInventoryTest

//2025_11_19_091736_create_ghe_table.php
