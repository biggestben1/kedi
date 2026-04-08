<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuperAdminLandingSliderController extends Controller
{
    public function index()
    {
        $sliders = LandingSlider::orderBy('sort_order')->get();
        return view('admin.landing_sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('admin.landing_sliders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('landing/sliders', 'public');
            $data['image'] = $path;
        }

        LandingSlider::create($data);

        return redirect()->route('admin.landing-sliders.index')->with('success', 'Slider image added successfully.');
    }

    public function edit(LandingSlider $landingSlider)
    {
        return view('admin.landing_sliders.edit', compact('landingSlider'));
    }

    public function update(Request $request, LandingSlider $landingSlider)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image
            if ($landingSlider->image && !str_starts_with($landingSlider->image, 'images/')) {
                Storage::disk('public')->delete($landingSlider->image);
            }
            $path = $request->file('image')->store('landing/sliders', 'public');
            $data['image'] = $path;
        }

        $landingSlider->update($data);

        return redirect()->route('admin.landing-sliders.index')->with('success', 'Slider image updated successfully.');
    }

    public function destroy(LandingSlider $landingSlider)
    {
        if ($landingSlider->image && !str_starts_with($landingSlider->image, 'images/')) {
            Storage::disk('public')->delete($landingSlider->image);
        }
        $landingSlider->delete();

        return redirect()->route('admin.landing-sliders.index')->with('success', 'Slider image deleted successfully.');
    }

    public function toggleActive(LandingSlider $landingSlider)
    {
        $landingSlider->update(['is_active' => !$landingSlider->is_active]);
        return response()->json(['success' => true, 'is_active' => $landingSlider->is_active]);
    }
}
