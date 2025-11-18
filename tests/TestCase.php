<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App; // BẮT BUỘC: Import Facade App

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Thiết lập các cấu hình cần thiết trước khi chạy mỗi bài test.
     * Phương thức này chạy trước mỗi kịch bản test.
     */
    protected function setUp(): void
    {
        // Phải gọi parent::setUp() trước
        parent::setUp();

        // FIX LỖI: Buộc môi trường test sử dụng Locale 'vi' (Tiếng Việt).
        // Lệnh này phải nằm TRONG phương thức setUp().
        App::setLocale('vi');
    }
}
