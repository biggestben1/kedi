<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expenditure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SuperAdminExpenditureController extends Controller
{
    public function index(Request $request)
    {
        $query = Expenditure::latest();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $expenditures = $query->paginate(15)->appends($request->all());
        
        $categories = [
            'Rent', 'Utilities', 'Salaries', 'Office Supplies', 'Travel', 
            'Training', 'Maintenance', 'Marketing', 'Taxes', 'Insurance', 'Other'
        ];

        return view('admin.expenditures.index', compact('expenditures', 'categories'));
    }

    public function create()
    {
        $categories = [
            'Rent',
            'Utilities',
            'Salaries',
            'Office Supplies',
            'Travel',
            'Training',
            'Maintenance',
            'Marketing',
            'Taxes',
            'Insurance',
            'Other',
        ];
        return view('admin.expenditures.create', compact('categories'));
    }

    public function store(Request $request)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => str_replace(',', '', $request->amount)]);
        }

        $data = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
        $data['created_by'] = Auth::id();
        Expenditure::create($data);
        return redirect()->route('admin.expenditures.index')->with('success', 'Expenditure created.');
    }

    public function show(Expenditure $expenditure)
    {
        return view('admin.expenditures.show', compact('expenditure'));
    }

    public function edit(Expenditure $expenditure)
    {
        $categories = [
            'Rent',
            'Utilities',
            'Salaries',
            'Office Supplies',
            'Travel',
            'Training',
            'Maintenance',
            'Marketing',
            'Taxes',
            'Insurance',
            'Other',
        ];
        return view('admin.expenditures.edit', compact('expenditure', 'categories'));
    }

    public function update(Request $request, Expenditure $expenditure)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => str_replace(',', '', $request->amount)]);
        }

        $data = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
        $expenditure->update($data);
        return redirect()->route('admin.expenditures.index')->with('success', 'Expenditure updated.');
    }

    public function destroy(Expenditure $expenditure)
    {
        $expenditure->delete();
        return redirect()->route('admin.expenditures.index')->with('success', 'Expenditure deleted.');
    }
}
