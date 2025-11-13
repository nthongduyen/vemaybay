<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.NguoiDung::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'so_dien_thoai' => ['nullable', 'string', 'digits:10', 'regex:/^0/'],
            'dia_chi' => ['nullable', 'string', 'max:255'],
        ],[
            // (Tùy chọn) Thêm thông báo lỗi Tiếng Việt
            'so_dien_thoai.digits' => 'Số điện thoại phải đủ 10 chữ số.',
            'so_dien_thoai.regex' => 'Số điện thoại phải bắt đầu bằng số 0.',
        ]);

        // ---- BẮT ĐẦU THAY ĐỔI ----
        $user = NguoiDung::create([
            'ho_ten' => $request->name, // Ánh xạ 'name' -> 'ho_ten'
            'email' => $request->email,
            'mat_khau' => Hash::make($request->password),
            'so_dien_thoai' => $request->so_dien_thoai,
            'dia_chi' => $request->dia_chi,
            'vai_tro' => 'khach_hang',
            'trang_thai' => 'hoat_dong',
        ]);
        // ---- KẾT THÚC THAY ĐỔI ----

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
