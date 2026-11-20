<?php

namespace App\Http\Controllers\PublicSite;

class InformationController
{
    public function faq()
    {
        return view('omar.information.faq');
    }

    public function terms()
    {
        return view('omar.information.terms');
    }
}
