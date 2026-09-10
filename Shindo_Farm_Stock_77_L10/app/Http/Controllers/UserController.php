<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public const ALLOWED_ROLES = ['super_admin'];

    public function index(Request $request)
    {
        $query = User::orderBy('name');

        if ($request->ajax()) {
            return response()->json(['data' => $query->get()]);
        }

        $data = $query->get();

        return view('user.index', compact('data'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:super_admin,admin,staf_ayam,staf_keuangan',
            ],
            [
                'name.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 6 karakter.',
                'role.required' => 'Role wajib dipilih.',
                'role.in' => 'Role tidak valid.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan.',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'role' => 'required|in:super_admin,admin,staf_ayam,staf_keuangan',
            ],
            [
                'name.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
                'password.min' => 'Password minimal 6 karakter.',
                'role.required' => 'Role wajib dipilih.',
                'role.in' => 'Role tidak valid.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Proteksi: tidak bisa menurunkan role akun sendiri (cegah lockout super_admin)
        if ($user->id === auth()->id() && $request->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menurunkan role akun sendiri.',
            ], 422);
        }

        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diperbarui.',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        // Proteksi: tidak bisa menghapus akun sendiri
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun sendiri.',
            ], 409);
        }

        try {
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus.',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        // Proteksi: tidak bisa reset password akun sendiri dari sini
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Gunakan menu Profil untuk mengubah password sendiri.',
            ], 409);
        }

        try {
            $user->update(['password' => Hash::make('ADMIN77')]);

            return response()->json([
                'success' => true,
                'message' => "Password {$user->name} berhasil direset ke 'ADMIN77'.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset password: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function statistics($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        // 12 bulan terakhir (termasuk bulan berjalan)
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'label' => $date->translatedFormat('M Y'),
                'year' => (int) $date->format('Y'),
                'month' => (int) $date->format('m'),
            ];
        }

        // Jumlah aktivitas per model per bulan (dari activity_logs)
        $models = ['Kandang', 'Telur', 'Penjualan', 'Pengeluaran', 'User'];
        $stats = [];
        foreach ($models as $model) {
            $row = [];
            foreach ($months as $m) {
                $row[] = ActivityLog::where('user_id', $user->id)
                    ->where('logable_type', $model)
                    ->whereYear('created_at', $m['year'])
                    ->whereMonth('created_at', $m['month'])
                    ->count();
            }
            $stats[$model] = $row;
        }

        $total = ActivityLog::where('user_id', $user->id)->count();

        // 5 aktivitas terbaru
        $recent = ActivityLog::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get(['action', 'logable_type as model', 'description', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'labels' => array_column($months, 'label'),
                'stats' => $stats,
                'total' => $total,
                'recent' => $recent,
            ],
        ]);
    }

    public function activityHistory(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        // Ambil SEMUA log tanpa filter user, join users untuk nama
        $logs = ActivityLog::select('activity_logs.*')
            ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
            ->selectRaw('activity_logs.id, activity_logs.user_id, activity_logs.action, activity_logs.logable_type as model, activity_logs.description, activity_logs.created_at, users.name as user_name')
            ->whereYear('activity_logs.created_at', $tahun)
            ->whereMonth('activity_logs.created_at', $bulan)
            ->orderBy('activity_logs.created_at', 'desc')
            ->paginate(20);

        // Daftar bulan yang punya data
        $availableMonths = ActivityLog::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function ($row) {
                $dt = \Carbon\Carbon::createFromDate($row->year, $row->month, 1);
                return [
                    'year'  => $row->year,
                    'month' => $row->month,
                    'label' => $dt->translatedFormat('F Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'logs'            => $logs,
                'availableMonths' => $availableMonths,
                'currentMonth'    => $bulan,
                'currentYear'     => $tahun,
            ],
        ]);
    }
}