<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class SuperAdminBankController extends Controller
{
    public function index()
    {
        $banks = Bank::orderBy('name')->get();
        return view('admin.banks.index', ['banks' => $banks]);
    }

    public function create()
    {
        return view('admin.banks.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        Bank::create($data);
        return redirect()->route('admin.banks.index')->with('success', 'Bank created.');
    }

    public function edit(Bank $bank)
    {
        return view('admin.banks.edit', ['bank' => $bank]);
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $bank->update($data);
        return redirect()->route('admin.banks.index')->with('success', 'Bank updated.');
    }

    public function deactivate(Bank $bank)
    {
        $bank->update(['is_active' => false]);
        return redirect()->route('admin.banks.edit', $bank)->with('success', 'Bank deactivated.');
    }

    public function activate(Bank $bank)
    {
        $bank->update(['is_active' => true]);
        return redirect()->route('admin.banks.edit', $bank)->with('success', 'Bank activated.');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return redirect()->route('admin.banks.index')->with('success', 'Bank deleted.');
    }
}
