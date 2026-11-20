<?php

namespace App\Http\Controllers\PublicSite;

use Illuminate\Contracts\View\View;

class HomeController
{
    public function index(): View
    {
        return view('welcome');
    }
}
