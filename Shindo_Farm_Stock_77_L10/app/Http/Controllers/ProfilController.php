<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public const ALLOWED_ROLES = ['super_admin', 'admin', 'staf_ayam', 'staf_keuangan'];

    public function index()
    {
        $user = Auth::user();

        return view('profil.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('tab', 'edit');
        }

        $user->update($request->only(['name', 'email']));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $user = Auth::user();

        $validator = \Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('tab', 'password');
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ])->withInput()->with('tab', 'password');
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function deactivate(Request $request)
    {
        $user = Auth::user();

        $validator = \Validator::make($request->all(), [
            'password' => 'required|string',
        ], [
            'password.required' => 'Password wajib diisi untuk konfirmasi.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('tab', 'deactivate');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Password tidak sesuai. Penonaktifan dibatalkan.',
            ])->withInput()->with('tab', 'deactivate');
        }

        // Nonaktifkan: set role NULL agar data relasi tetap aman, lalu logout
        $user->update(['role' => null]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Akun berhasil dinonaktifkan.');
    }
}
