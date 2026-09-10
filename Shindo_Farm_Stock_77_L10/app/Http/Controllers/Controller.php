<?php

namespace App\Http\Controllers;

use App\Traits\HasRoleAccess;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use HasRoleAccess;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            return $this->checkRoleAccess($request) ?? $next($request);
        });
    }
}
