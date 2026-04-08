<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Display the SEO-optimized landing page.
     */
    public function index()
    {
        $about = \App\Models\AboutUs::first();
        $services = \App\Models\Service::where('is_active', true)->orderBy('sort_order')->get();
        $sliders = \App\Models\LandingSlider::where('is_active', true)->orderBy('sort_order')->get();
        return view('landing', compact('about', 'services', 'sliders'));
    }
}
