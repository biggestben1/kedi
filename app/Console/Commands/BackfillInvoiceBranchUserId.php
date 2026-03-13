<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class BackfillInvoiceBranchUserId extends Command
{
    protected $signature = 'invoices:backfill-branch-user-id';

    protected $description = 'Set branch_user_id for invoices where customer is Branch but branch_user_id is null';

    public function handle(): int
    {
        $invoices = Invoice::with('user.role')
            ->whereNull('branch_user_id')
            ->whereNotNull('user_id')
            ->get();

        $updated = 0;
        foreach ($invoices as $invoice) {
            $branchUserId = $this->getFulfillingBranchId($invoice->user);
            if ($branchUserId) {
                $invoice->update(['branch_user_id' => $branchUserId]);
                $this->line("Updated invoice {$invoice->invoice_number} (ID: {$invoice->id})");
                $updated++;
            }
        }

        $this->info("Updated {$updated} invoice(s).");

        return Command::SUCCESS;
    }

    private function getFulfillingBranchId($user): ?int
    {
        if (! $user || ! $user->role) {
            return null;
        }
        $role = $user->role->name;
        if ($role === 'branch') {
            return (int) $user->id;
        }
        if (in_array($role, ['annex', 'service_center'], true) && $user->created_by_user_id) {
            $parent = \App\Models\User::with('role')->find($user->created_by_user_id);
            return $parent && $parent->role?->name === 'branch'
                ? (int) $parent->id
                : ($parent ? $this->getFulfillingBranchId($parent) : null);
        }
        return null;
    }
}
