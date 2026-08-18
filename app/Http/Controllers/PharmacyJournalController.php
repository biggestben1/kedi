<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyJournalController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : Carbon::now()->endOfDay();

        $entries = JournalEntry::query()
            ->with('lines')
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.pharmacy.journal.index', [
            'entries' => $entries,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ]);
    }

    public function create()
    {
        return view('admin.pharmacy.journal.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_name' => ['required', 'string', 'max:255'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = collect($validated['lines'] ?? [])
            ->map(function ($line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                return [
                    'account_name' => trim((string) ($line['account_name'] ?? '')),
                    'description' => ($line['description'] ?? null) !== null ? trim((string) $line['description']) : null,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            })
            ->filter(fn ($l) => $l['account_name'] !== '');

        if ($lines->count() < 2) {
            return back()->withErrors(['lines' => 'Add at least 2 journal lines.'])->withInput();
        }

        $totalDebit = round((float) $lines->sum('debit'), 2);
        $totalCredit = round((float) $lines->sum('credit'), 2);

        if ($totalDebit <= 0 && $totalCredit <= 0) {
            return back()->withErrors(['lines' => 'Enter at least one debit or credit amount.'])->withInput();
        }

        if ($totalDebit !== $totalCredit) {
            return back()->withErrors(['lines' => 'Total debit must equal total credit.'])->withInput();
        }

        DB::transaction(function () use ($validated, $lines, $request) {
            /** @var JournalEntry $entry */
            $entry = JournalEntry::create([
                'entry_date' => $validated['entry_date'],
                'reference' => $validated['reference'] ?? null,
                'type' => $validated['type'] ?? null,
                'memo' => $validated['memo'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $entry->lines()->createMany($lines->map(function ($l) {
                return [
                    'account_name' => $l['account_name'],
                    'description' => $l['description'],
                    'debit' => $l['debit'],
                    'credit' => $l['credit'],
                ];
            })->all());
        });

        return redirect()->route('admin.pharmacy.journal.index')->with('success', 'Journal entry created.');
    }
}

