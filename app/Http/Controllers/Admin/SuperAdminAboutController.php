<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuperAdminAboutController extends Controller
{
    public function edit()
    {
        $about = AboutUs::firstOrCreate([
            'id' => 1
        ], [
            'title' => 'Holistic Wellness Center Lagos',
            'content' => 'Welcome to Optimal Consult, the leading wellness center in Lagos Nigeria. We believe in the power of natural healing and preventive healthcare. Our clinic provides a serene environment for your journey toward complete physical and mental well-being.' . "\n\n" . 'Whether you are looking for drug-free treatment in Lagos or simply want to optimize your health through natural methods, our team is here to guide you. We focus on treating the root cause of ailments, not just the symptoms.',
            'button_text' => 'Learn More',
            'button_link' => 'tel:08067131990',
        ]);

        return view('admin.about.edit', compact('about'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $about = AboutUs::first();

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            if ($about->image) {
                Storage::delete('public/' . $about->image);
            }
            $imagePath = $request->file('image')->store('about', 'public');
            $data['image'] = $imagePath;
        }

        $about->update($data);

        return redirect()->back()->with('success', 'About Us content updated successfully.');
    }
}
