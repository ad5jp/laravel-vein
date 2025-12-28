<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function init(): View
    {
        return view('vein::home', []);
    }
}
