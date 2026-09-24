<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        return $request->user()->isStaff() || $request->user()->isManager()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('portal.dashboard');
    }
}
