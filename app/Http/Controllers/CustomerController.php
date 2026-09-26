<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $showArchived = $request->boolean('archived');

        $customers = Customer::withCount('sales', 'repairJobs')
            ->withSum('receivables', 'balance')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                                         ->orWhere('contact_person', 'like', "%{$search}%"))
            ->where('status', $showArchived ? 'archived' : 'active')
            ->orderBy('name')
            ->get();

        return view('customers.index', [
            'customers'    => $customers,
            'search'       => $search,
            'showArchived' => $showArchived,
            'activeCount'   => Customer::active()->count(),
            'archivedCount' => Customer::where('status', 'archived')->count(),
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        Customer::create($this->validated($request));

        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    public function edit(Customer $customer)
    {
        $customer->loadCount('sales', 'repairJobs');

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request, $customer));

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    // Archive instead of delete: transaction history stays intact
    public function toggleArchive(Customer $customer)
    {
        if ($customer->isArchived()) {
            $customer->update(['status' => 'active']);
            $message = "{$customer->name} restored.";
        } else {
            $outstanding = $customer->receivables()->where('status', '!=', 'paid')->sum('balance');

            if ($outstanding > 0) {
                return back()->withErrors([
                    'archive' => "Cannot archive {$customer->name}: ₱"
                        . number_format($outstanding, 2) . ' still outstanding.',
                ]);
            }

            $customer->update(['status' => 'archived']);
            $message = "{$customer->name} archived. Past records are kept.";
        }

        return redirect()->route('customers.index')->with('success', $message);
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('customers', 'name')->ignore($customer),
            ],
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string|max:255',
        ], [
            'name.unique' => 'A customer with this name already exists.',
        ]);
    }
}