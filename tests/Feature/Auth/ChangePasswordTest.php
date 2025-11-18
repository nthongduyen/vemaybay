<?php

namespace Tests\Feature\Auth;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Mockery;

/**
 * Kiểm tra các Test Case cho chức năng Đổi mật khẩu.
 * Giả định endpoint là POST /profile/password và yêu cầu người dùng đã đăng nhập.
 * Giả định validation: required, confirmed, min:9, và rule phức tạp.
 */
class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    // Lưu trữ kết quả thực tế của Test Case
    protected static array $testResults = [];
    protected NguoiDung $user;
    protected string $initialPassword = 'OldPass@54321'; // Mật khẩu cũ ban đầu

    /**
     * Hàm tiện ích để ghi lại kết quả của từng Test Case vào mảng tĩnh.
     */
    protected function recordResult(string $id, string $description, string $input, string $expected, bool $passed): void
    {
        // Ghi nhận kết quả vào mảng tĩnh
        self::$testResults[] = [
            'ID' => $id,
            'Mô tả' => $description,
            'Dữ liệu đầu vào' => $input,
            'Kết quả Mong đợi' => $expected,
            'Trạng thái' => $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset biến static cho mỗi lần chạy test suite
        if (self::$testResults === null || !is_array(self::$testResults)) {
            self::$testResults = [];
        }

        // Tạo và đăng nhập một người dùng
        $this->user = NguoiDung::factory()->create([
            'email' => 'testuser@example.com',
            'ho_ten' => 'Người Dùng Test',
            'mat_khau' => Hash::make($this->initialPassword),
        ]);

        // Đăng nhập người dùng này cho tất cả các test
        // Dùng actingAs để giả lập request từ người dùng đã đăng nhập
        $this->actingAs($this->user);

        // Giả lập việc không gửi email/notification
        Notification::fake();
    }

    // --- CÁC TEST CASE ĐỔI MẬT KHẨU (UT13 đến UT26) ---

    /**
     * UT13: Kiểm tra đổi mật khẩu thành công.
     */
    public function test_ut13_successful_password_change(): void
    {
        $newPassword = 'Pass@12345';
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "' . $newPassword . '" / Confirm: "' . $newPassword . '"';

        // 1. Thực hiện Request (POST)
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định (Assertions)
        // Kiểm tra chuyển hướng thành công
        $response->assertSessionHas('status', 'Mật khẩu của bạn đã được đổi thành công!');
        $response->assertStatus(302); // Quay lại trang profile

        // Tải lại người dùng từ DB để kiểm tra mật khẩu
        $updatedUser = $this->user->fresh();

        // Kiểm tra mật khẩu mới đã được lưu và đúng
        $isPasswordUpdated = Hash::check($newPassword, $updatedUser->mat_khau);
        $isOldPasswordUsed = Hash::check($this->initialPassword, $updatedUser->mat_khau);

        $this->assertTrue($isPasswordUpdated, 'UT13: Mật khẩu mới không được lưu đúng trong CSDL.');
        $this->assertFalse($isOldPasswordUsed, 'UT13: Mật khẩu cũ vẫn còn hiệu lực.');
        $response->assertSessionDoesntHaveErrors();

        // 3. Ghi lại kết quả
        $this->recordResult('UT13', 'Đổi mật khẩu thành công', $input_display, 'Hệ thống hiển thị thông báo: "Mật khẩu của bạn đã được đổi thành công!" Cập nhật mật khẩu mới trong CSDL.', true);
    }

    /**
     * UT14: Kiểm tra khi mật khẩu mới trống.
     */
    public function test_ut14_empty_new_password(): void
    {
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => '',
            'password_confirmation' => 'Pass@12345',
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "" / Confirm: "Pass@12345"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        $response->assertSessionHasErrors(['password']);
        $response->assertStatus(302); // Quay lại form

        // 3. Ghi lại kết quả
        $this->recordResult('UT14', 'Mật khẩu mới trống', $input_display, 'Hệ thống hiển thị thông báo lỗi: "Vui lòng nhập mật khẩu mới!"', true);
    }

    /**
     * UT15: Kiểm tra khi trường xác nhận mật khẩu trống.
     */
    public function test_ut15_empty_password_confirmation(): void
    {
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => 'Pass@12345',
            'password_confirmation' => '',
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "Pass@12345" / Confirm: ""';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        // Lỗi sẽ là lỗi 'confirmed' do trường 'password_confirmation' bị thiếu.
        $response->assertSessionHasErrors(['password']);
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT15', 'Trường xác nhận mật khẩu trống', $input_display, 'Hệ thống hiển thị thông báo lỗi: "Vui lòng nhập xác nhận mật khẩu!"', true);
    }

    /**
     * UT16: Kiểm tra khi cả hai trường (password & confirmation) đều trống.
     */
    public function test_ut16_both_fields_are_empty(): void
    {
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => '',
            'password_confirmation' => '',
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "" / Confirm: ""';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        // Lỗi sẽ là lỗi 'required' trên trường 'password'
        $response->assertSessionHasErrors(['password']);
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT16', 'Cả hai trường đều trống', $input_display, 'Hệ thống hiển thị thông báo lỗi: "Vui lòng nhập đầy đủ mật khẩu mới và xác nhận!"', true);
    }

    /**
     * UT17: Kiểm tra khi mật khẩu xác nhận không khớp.
     */
    public function test_ut17_password_confirmation_mismatch(): void
    {
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => 'Pass@12345',
            'password_confirmation' => 'NewPass@4321', // Không khớp
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "Pass@12345" / Confirm: "NewPass@4321"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        $response->assertSessionHasErrors(['password']);
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT17', 'Mật khẩu xác nhận không khớp', $input_display, 'Hệ thống hiển thị thông báo lỗi: "Xác nhận mật khẩu không khớp." (Mã lỗi Laravel)', true);
    }

    /**
     * UT19: Kiểm tra độ dài tối thiểu (ít hơn 9 ký tự).
     */
    public function test_ut19_minimum_length_violation(): void
    {
        $shortPass = 'Pass@123'; // 8 ký tự
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => $shortPass,
            'password_confirmation' => $shortPass,
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "' . $shortPass . '" / Confirm: "' . $shortPass . '"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        $response->assertSessionHasErrors(['password']);
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT19', 'Mật khẩu ít hơn 9 ký tự', $input_display, 'Hệ thống hiển thị thông báo lỗi: "Mật khẩu phải có ít nhất 9 ký tự!"', true);
    }

    /**
     * UT20: Kiểm tra giới hạn ký tự (chứa dấu cách, giả định không hợp lệ).
     * Dựa trên rule 'regex:/\S+/' trong UpdatePasswordRequest, dấu cách sẽ bị chặn.
     */
    public function test_ut20_invalid_character_violation(): void
    {
        $invalidPass = 'Pass @ 1234'; // Chứa dấu cách (Sẽ bị chặn bởi regex)
        $data = [
            'current_password' => $this->initialPassword, // BẮT BUỘC phải có
            'password' => $invalidPass,
            'password_confirmation' => $invalidPass,
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "' . $invalidPass . '" / Confirm: "' . $invalidPass . '"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        $response->assertSessionHasErrors(['password']);
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT20', 'Mật khẩu chứa ký tự không hợp lệ (dấu cách)', $input_display, 'Hệ thống hiển thị thông báo lỗi: "Mật khẩu không được chứa khoảng trắng..."', true);
    }

    // --- TEST CASE BỔ SUNG CẦN THIẾT ---

    /**
     * UT22: Kiểm tra khi mật khẩu hiện tại không chính xác.
     */
    public function test_ut22_incorrect_current_password(): void
    {
        $newPassword = 'NewPass@99999';
        $wrongCurrentPass = 'WrongPass@12345';
        $data = [
            'current_password' => $wrongCurrentPass, // Mật khẩu hiện tại sai
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];
        $input_display = 'Current: "' . $wrongCurrentPass . '" / New: "' . $newPassword . '"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        $response->assertSessionHasErrors(['current_password']);
        $response->assertStatus(302);

        // Kiểm tra mật khẩu trong DB không thay đổi
        $updatedUser = $this->user->fresh();
        $this->assertTrue(Hash::check($this->initialPassword, $updatedUser->mat_khau), 'UT22: Mật khẩu đã bị thay đổi dù mật khẩu hiện tại sai.');

        // 3. Ghi lại kết quả
        $this->recordResult('UT22', 'Mật khẩu hiện tại không chính xác', $input_display, 'Lỗi Validation: "Mật khẩu hiện tại không chính xác."', true);
    }

    /**
     * UT23: Kiểm tra khi mật khẩu hiện tại bị bỏ trống.
     */
    public function test_ut23_missing_current_password(): void
    {
        $newPassword = 'NewPass@99999';
        $data = [
            'current_password' => '', // Bị bỏ trống
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];
        $input_display = 'Current: "" / New: "' . $newPassword . '"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        // Lỗi do rule 'required'
        $response->assertSessionHasErrors(['current_password']);
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT23', 'Mật khẩu hiện tại bị bỏ trống', $input_display, 'Lỗi Validation: "Mật khẩu hiện tại không được để trống."', true);
    }

    /**
     * UT26: Kiểm tra mật khẩu mới trùng mật khẩu cũ.
     * Kiểm tra logic 'different:current_password' trong UpdatePasswordRequest.
     */
    public function test_ut26_new_password_is_same_as_old(): void
    {
        $data = [
            'current_password' => $this->initialPassword,
            'password' => $this->initialPassword, // Trùng mật khẩu cũ
            'password_confirmation' => $this->initialPassword,
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "' . $this->initialPassword . '"';

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định
        $response->assertSessionHasErrors(['password']); // Lỗi trên trường password
        $response->assertStatus(302);

        // 3. Ghi lại kết quả
        $this->recordResult('UT26', 'Mật khẩu mới trùng mật khẩu cũ', $input_display, 'Lỗi Validation: "Mật khẩu mới không được trùng với mật khẩu hiện tại."', true);
    }

    /**
     * UT18: Kiểm tra lỗi hệ thống khi cập nhật mật khẩu (Mô phỏng lỗi DB).
     * Mocking được thiết lập lại để mô phỏng lỗi trong phương thức `save()` của Model.
     */
    public function test_ut18_system_error_on_password_update(): void
    {
        // 1. Chuẩn bị dữ liệu
        $newPassword = 'FailPass@12345';
        $data = [
            'current_password' => $this->initialPassword, // Phải chính xác để pass bước 1 (Validation)
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];
        $input_display = 'Current: "' . $this->initialPassword . '" / New: "' . $newPassword . '"';

        // --- BẮT ĐẦU MOCKING ---
        // Tạo Mock cho người dùng hiện tại
        $mockUser = Mockery::mock(NguoiDung::class)->makePartial();

        // Thiết lập các thuộc tính cần thiết để request pass Validation (bước 1)
        $mockUser->id = $this->user->id;
        $mockUser->MaNguoiDung = $this->user->MaNguoiDung; // Dùng khóa chính của Model NguoiDung
        $mockUser->mat_khau = $this->user->mat_khau; // Mật khẩu hash ban đầu

        // Mock setAttribute để Controller có thể gán $user->mat_khau = Hash::make(...)
        $mockUser->shouldReceive('setAttribute')
                 ->with('mat_khau', Mockery::type('string'))
                 ->andReturnSelf();

        // Mock save() để ném ra Exception, mô phỏng lỗi DB
        $mockUser->shouldReceive('save')
                 ->andThrow(new \Exception('Database write failed'));

        // Thay thế đối tượng User trong Auth bằng mock object
        Auth::shouldReceive('user')->andReturn($mockUser);

        // 1. Thực hiện Request
        $response = $this->post('/profile/password', $data);

        // 2. Khẳng định (Assertions)
        // Controller sẽ bắt Exception và trả về session 'error'
        $response->assertSessionHas('error', 'Đổi mật khẩu thất bại, vui lòng thử lại sau!');
        $response->assertStatus(302);

        // Kiểm tra mật khẩu KHÔNG thay đổi trong DB (Quan trọng: phải làm sạch Mock Auth)
        Auth::clearResolvedInstances();
        $updatedUser = NguoiDung::find($this->user->MaNguoiDung); // Lấy user thật từ DB
        $isPasswordUnchanged = Hash::check($this->initialPassword, $updatedUser->mat_khau);
        $isNewPasswordUsed = Hash::check($newPassword, $updatedUser->mat_khau);

        $this->assertTrue($isPasswordUnchanged, 'UT18: Mật khẩu bị thay đổi dù giả định lỗi hệ thống.');
        $this->assertFalse($isNewPasswordUsed, 'UT18: Mật khẩu mới đã được lưu dù giả định lỗi hệ thống.');

        // 3. Ghi lại kết quả
        $this->recordResult('UT18', 'Lỗi hệ thống khi cập nhật mật khẩu (Mô phỏng lỗi DB)', $input_display, 'Hệ thống hiển thị thông báo: "Đổi mật khẩu thất bại, vui lòng thử lại sau!" Mật khẩu KHÔNG được cập nhật.', true);
    }

    /**
     * Hàm này được gọi sau khi tất cả các test trong class đã chạy xong.
     * Dùng để in báo cáo kết quả ra console dưới dạng CSV.
     */
    public static function tearDownAfterClass(): void
    {
        if (empty(self::$testResults)) {
            return;
        }

        // 1. Loại bỏ thẻ màu sắc (<fg=green>PASS</>) trước khi xuất CSV
        $results = array_map(function($row) {
            $row['Trạng thái'] = strip_tags($row['Trạng thái']);
            return $row;
        }, self::$testResults);

        // Lấy headers
        $headers = array_keys($results[0]);

        // 2. Tạo chuỗi CSV trong bộ nhớ tạm
        $output = fopen('php://temp', 'r+');

        // Ghi tiêu đề (Headers)
        fputcsv($output, $headers);

        // Ghi dữ liệu
        foreach ($results as $row) {
            fputcsv($output, array_values($row));
        }

        // Đặt con trỏ về đầu stream để đọc nội dung
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // 3. FIX LỖI FONT TIẾNG VIỆT TRÊN EXCEL/WPS: Thêm Byte Order Mark (BOM) cho UTF-8
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $csvContentWithBOM = $bom . $csvContent;

        // 4. IN NỘI DUNG CSV RA CONSOLE (STDOUT)
        echo "\n\n========================================================================\n";
        echo "BÁO CÁO KẾT QUẢ TEST CASE ĐỔI MẬT KHẨU (ĐỊNH DẠNG CSV IN RA CONSOLE)\n";
        echo "========================================================================\n\n";
        // In nội dung KHÔNG CÓ BOM ra Console
        echo $csvContent;
        echo "\n";


        // 5. LƯU CHUỖI CSV CÓ BOM VÀO FILE VẬT LÝ
        $filePath = base_path('password_update_test_report_DMK.csv');

        // Ta không thể lưu file vật lý trong môi trường Canvas, chỉ in ra console
    }
}
//file liên quan: app/http/controller/ProfileController.php -app/Http/Requests/UpdatePasswordRequest.php - app/Models/NguoiDung.php
// database/factories/NguoiDungFactory

/// lệnh chạy:  php artisan test --filter=ChangePasswordTest
