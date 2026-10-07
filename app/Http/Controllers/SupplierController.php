<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $showArchived = $request->boolean('archived');

        $suppliers = Supplier::withCount('invoices')
            ->withSum(['invoices as outstanding' => fn ($q) => $q->where('status', '!=', 'paid')], 'balance')
            ->where('status', $showArchived ? 'archived' : 'active')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get();

        return view('suppliers.index', [
            'suppliers'     => $suppliers,
            'search'        => $search,
            'showArchived'  => $showArchived,
            'activeCount'   => Supplier::active()->count(),
            'archivedCount' => Supplier::where('status', 'archived')->count(),
        ]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function edit(Supplier $supplier)
    {
        $supplier->loadCount('invoices');

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        // New credit terms apply to invoices recorded from now on;
        // due dates of existing invoices do not change.
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    // Archive instead of delete: invoices and payments stay intact
    public function toggleArchive(Supplier $supplier)
    {
        if ($supplier->isArchived()) {
            $supplier->update(['status' => 'active']);
            $message = "{$supplier->name} restored.";
        } else {
            $outstanding = $supplier->invoices()->where('status', '!=', 'paid')->sum('balance');

            if ($outstanding > 0) {
                return back()->withErrors([
                    'archive' => "Cannot archive {$supplier->name}: ₱"
                        . number_format($outstanding, 2) . ' is still unpaid.',
                ]);
            }

            $supplier->update(['status' => 'archived']);
            $message = "{$supplier->name} archived. Past invoices and payments are kept.";
        }

        return redirect()->route('suppliers.index')->with('success', $message);
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('suppliers', 'name')->ignore($supplier),
            ],
            'contact_person'    => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:30',
            'credit_terms_days' => 'required|integer|min:0|max:365',
        ], [
            'name.unique' => 'A supplier with this name already exists.',
        ]);
    }
}