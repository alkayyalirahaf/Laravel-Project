<?php

namespace App\Http\Controllers\PublicSite;

class InformationController
{
    public function faq()
    {
        return view('pages.information', ['page' => 'faq', 'title' => 'Frequently asked questions']);
    }

    public function terms()
    {
        return view('pages.information', ['page' => 'terms', 'title' => 'Terms and conditions']);
    }
}
