<?php

namespace App\Http\Controllers\PublicSite;

use App\Models\Country;
use Illuminate\Http\Request;

class DestinationController
{
    public function index()
    {
        $countries = Country::with('cities.airports')->orderBy('name')->get();

        return view('pages.destinations', compact('countries'));
    }
}
