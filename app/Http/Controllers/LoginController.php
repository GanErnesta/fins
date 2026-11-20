<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // Menampilkan halaman login
    public function showLogin()
    {
        return view('login');
    }

    // Memproses login mahasiswa
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nim' => [
                'required',
                'digits_between:1,20'
            ],
            'password' => [
                'required',
                'string'
            ],
        ]);

        if (Auth::attempt($credentials)) {

            // Regenerasi session setelah login
            $request->session()->regenerate();

            // Arahkan pengguna ke home
            return redirect()->intended(route('home'));
        }

        return back()
            ->withErrors([
                'nim' => 'NIM atau password salah.',
            ])
            ->onlyInput('nim');
    }

    // Logout pengguna
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
