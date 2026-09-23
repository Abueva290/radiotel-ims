<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableController extends Controller
{
    public function index()
    {
        $receivables = Receivable::with('customer', 'sale', 'repairJob')
            ->orderByRaw("FIELD(status, 'unpaid', 'partial', 'paid')")
            ->orderBy('due_date')
            ->get();

        $stats = [
            'total'   => Receivable::sum('balance'),
            'overdue' => Receivable::overdue()->sum('balance'),
            'overdue_count' => Receivable::overdue()->count(),
        ];
        $stats['current'] = $stats['total'] - $stats['overdue'];

        $recent = ReceivablePayment::with('receivable.customer', 'receiver')
            ->latest('payment_date')
            ->latest('id')
            ->take(5)
            ->get();

        return view('receivables.index', compact('receivables', 'stats', 'recent'));
    }

    public function show(Receivable $receivable)
    {
        $receivable->load('customer', 'sale.items.product', 'repairJob', 'payments.receiver');

        return view('receivables.show', compact('receivable'));
    }

    public function storePayment(Request $request, Receivable $receivable)
    {
        $data = $request->validate([
            'amount_paid'    => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer',
            'payment_date'   => 'required|date',
        ]);

        if ($receivable->status === 'paid') {
            throw ValidationException::withMessages([
                'amount_paid' => 'This receivable is already fully paid.',
            ]);
        }

        if ($data['amount_paid'] > $receivable->balance) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Payment exceeds the remaining balance of ₱'
                    . number_format($receivable->balance, 2) . '.',
            ]);
        }

        DB::transaction(function () use ($receivable, $data, $request) {
            ReceivablePayment::create([
                'receivable_id'  => $receivable->id,
                'receipt_no'     => ReceivablePayment::nextReceiptNo(),
                'amount_paid'    => $data['amount_paid'],
                'payment_method' => $data['payment_method'],
                'payment_date'   => $data['payment_date'],
                'received_by'    => $request->user()->id,
            ]);

            $balance = $receivable->balance - $data['amount_paid'];

            $receivable->update([
                'balance' => $balance,
                'status'  => $balance <= 0 ? 'paid' : 'partial',
            ]);

            // Keep the sale's status in sync with its receivable
            if ($receivable->sale) {
                $receivable->sale->update([
                    'status' => $balance <= 0 ? 'paid' : 'partial',
                ]);
            }
        });

        return redirect()->route('receivables.show', $receivable)
            ->with('success', 'Payment recorded successfully.');
    }
}