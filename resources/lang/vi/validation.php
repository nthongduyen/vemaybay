<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dòng Ngôn ngữ Validation
    |--------------------------------------------------------------------------
    |
    | Các dòng ngôn ngữ sau đây chứa các thông báo lỗi mặc định được sử dụng
    | bởi class validator. Các quy tắc này có thể được điều chỉnh lại
    | theo ý muốn của bạn.
    |
    */

    'accepted'             => 'Trường :attribute phải được chấp nhận.',
    'active_url'           => 'Trường :attribute không phải là một URL hợp lệ.',
    'after'                => 'Trường :attribute phải là một ngày sau ngày :date.',
    'after_or_equal'       => 'Trường :attribute phải là thời gian sau hoặc bằng :date.',
    'alpha'                => 'Trường :attribute chỉ có thể chứa các chữ cái.',
    // ... (Các quy tắc khác nếu cần)

    'email'                => 'Trường :attribute phải là một địa chỉ email hợp lệ.',
    'max'                  => [
        'numeric' => 'Trường :attribute không được lớn hơn :max.',
        'file'    => 'Trường :attribute không được lớn hơn :max kilobytes.',
        'string'  => 'Trường :attribute không được lớn hơn :max ký tự.',
        'array'   => 'Trường :attribute không được có nhiều hơn :max mục.',
    ],
    'min'                  => [
        'numeric' => 'Trường :attribute phải có ít nhất :min.',
        'file'    => 'Trường :attribute phải có ít nhất :min kilobytes.',
        'string'  => 'Trường :attribute phải có ít nhất :min ký tự.',
        'array'   => 'Trường :attribute phải có ít nhất :min mục.',
    ],
    'required'             => 'Trường :attribute là bắt buộc.',
    'unique'               => 'Trường :attribute đã được sử dụng.',
    'confirmed'            => 'Trường :attribute xác nhận không khớp.',

    /*
    |--------------------------------------------------------------------------
    | Tên các thuộc tính (Attributes)
    |--------------------------------------------------------------------------
    |
    | Các dòng ngôn ngữ sau đây được sử dụng để hoán đổi placeholder
    | :attribute thành tên thuộc tính thân thiện hơn. Ví dụ: "email"
    | sẽ được đổi thành "địa chỉ email".
    |
    */

    'attributes' => [
        'ten' => 'tên',
        'email' => 'email',
        'password' => 'mật khẩu',
        // Thêm các tên trường khác nếu cần
    ],
];
