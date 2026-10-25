<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::with('lines.account');

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'memo' => 'nullable|string',
            'status' => 'in:draft,posted',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|company_exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        // Validate balanced entry
        $totalDebit = collect($validated['lines'])->sum(fn($l) => floatval($l['debit'] ?? 0));
        $totalCredit = collect($validated['lines'])->sum(fn($l) => floatval($l['credit'] ?? 0));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json(['message' => 'Journal entry must be balanced'], 422);
        }

        $entry = DB::transaction(function () use ($validated) {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'date' => $validated['date'],
                'reference' => $validated['reference'] ?? null,
                'memo' => $validated['memo'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'posted_at' => ($validated['status'] ?? 'draft') === 'posted' ? now() : null,
            ]);

            foreach ($validated['lines'] as $line) {
                if (empty($line['account_id'])) continue;

                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $entry;
        });

        return response()->json($entry->load('lines.account'), 201);
    }

    public function show(JournalEntry $journalEntry)
    {
        return $journalEntry->load('lines.account');
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return response()->json(['message' => 'Only draft entries can be edited'], 422);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'memo' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|company_exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        // Validate balanced entry
        $totalDebit = collect($validated['lines'])->sum(fn($l) => floatval($l['debit'] ?? 0));
        $totalCredit = collect($validated['lines'])->sum(fn($l) => floatval($l['credit'] ?? 0));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json(['message' => 'Journal entry must be balanced'], 422);
        }

        DB::transaction(function () use ($journalEntry, $validated) {
            $journalEntry->update([
                'date' => $validated['date'],
                'reference' => $validated['reference'] ?? null,
                'memo' => $validated['memo'] ?? null,
            ]);

            // Delete old lines and create new ones
            $journalEntry->lines()->delete();

            foreach ($validated['lines'] as $line) {
                if (empty($line['account_id'])) continue;

                $journalEntry->lines()->create([
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }
        });

        return response()->json($journalEntry->fresh()->load('lines.account'));
    }

    public function destroy(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return response()->json(['message' => 'Only draft entries can be deleted'], 422);
        }

        $journalEntry->delete();
        return response()->json(null, 204);
    }

    public function post(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return response()->json(['message' => 'Only draft entries can be posted'], 422);
        }

        $journalEntry->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        return response()->json($journalEntry->load('lines.account'));
    }
}
