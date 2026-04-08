<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class SuperAdminServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('sort_order')->get();
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'required|string|max:255',
            'sort_order' => 'integer',
        ]);

        Service::create($request->all());

        return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'required|string|max:255',
            'sort_order' => 'integer',
        ]);

        $service->update($request->all());

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'Service deleted successfully.');
    }

    public function toggleActive(Service $service)
    {
        $service->update(['is_active' => !$service->is_active]);
        return redirect()->back()->with('success', 'Service status updated.');
    }
}
