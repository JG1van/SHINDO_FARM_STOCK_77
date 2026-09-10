<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

trait HasRoleAccess
{
    /**
     * Check if the current user has access to this controller.
     *
     * This method checks the ALLOWED_ROLES constant defined in the controller
     * against the current authenticated user's role. If the user's role is not
     * in the allowed list, it returns a 403 response with the access denied view.
     *
     * For AJAX requests, it returns a JSON 403 response.
     * For regular requests, it returns the 403 blade view with role badges.
     *
     * Usage in controller constructor:
     * $this->middleware(function ($request, $next) {
     *     return $this->checkRoleAccess($request) ?? $next($request);
     * });
     *
     * @param \Illuminate\Http\Request|null $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|null
     *   - null = access granted, continue to next
     *   - Response = access denied (403 view or JSON)
     */
    protected function checkRoleAccess($request = null)
    {
        $allowedRoles = defined(static::class . '::ALLOWED_ROLES')
            ? static::ALLOWED_ROLES
            : null;

        // null = semua role boleh akses
        if ($allowedRoles === null) {
            return null;
        }

        $user = auth()->user();
        $userRole = $user->role ?? '';

        if (!in_array($userRole, $allowedRoles)) {
            // Jika request AJAX, return JSON 403
            if ($request && ($request->ajax() || $request->wantsJson())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Role Anda (' . $userRole . ') tidak diizinkan mengakses halaman ini.',
                ], 403);
            }

            // Jika request biasa, return view 403 dengan badge role
            return response()->view('errors.403', [
                'userRole' => $userRole,
                'allowedRoles' => $allowedRoles,
            ], 403);
        }

        return null;
    }

    /**
     * Helper statis untuk cek role di dalam method controller.
     * Berguna untuk proteksi tambahan di method tertentu.
     *
     * @param string $userRole Role user saat ini
     * @return bool True jika diizinkan, false jika tidak
     */
    public static function isRoleAllowed(string $userRole): bool
    {
        $allowedRoles = defined(static::class . '::ALLOWED_ROLES')
            ? static::ALLOWED_ROLES
            : null;

        if ($allowedRoles === null) {
            return true;
        }

        return in_array($userRole, $allowedRoles);
    }
}
