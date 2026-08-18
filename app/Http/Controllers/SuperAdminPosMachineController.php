<?php

namespace App\Http\Controllers;

use App\Models\PosMachine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuperAdminPosMachineController extends Controller
{
    public function index()
    {
        $posMachines = PosMachine::query()
            ->orderBy('bank_name')
            ->get();

        return view('admin.pos-machines.index', [
            'posMachines' => $posMachines,
        ]);
    }

    public function create()
    {
        return view('admin.pos-machines.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255', 'unique:pos_machines,account_number'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        PosMachine::create($data);

        return redirect()->route('admin.pos-machines.index')->with('success', 'Record created.');
    }

    public function edit(PosMachine $posMachine)
    {
        return view('admin.pos-machines.edit', [
            'posMachine' => $posMachine,
        ]);
    }

    public function update(Request $request, PosMachine $posMachine)
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pos_machines', 'account_number')->ignore($posMachine->id),
            ],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $posMachine->update($data);

        return redirect()->route('admin.pos-machines.index')->with('success', 'Record updated.');
    }

    public function destroy(PosMachine $posMachine)
    {
        $posMachine->delete();

        return redirect()->route('admin.pos-machines.index')->with('success', 'Record deleted.');
    }
}

