<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Supplier records: add, unique names, and archive instead of delete.
 */
class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function secretary(): User
    {
        return User::factory()->create(['role' => 'secretary']);
    }

    public function test_secretary_can_add_a_supplier(): void
    {
        $this->actingAs($this->secretary())
            ->post('/suppliers', [
                'name'              => 'Test Radio Supply',
                'contact_person'    => 'Juan Dela Cruz',
                'phone'             => '09171234567',
                'credit_terms_days' => 30,
            ])
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name'              => 'Test Radio Supply',
            'credit_terms_days' => 30,
            'status'            => 'active',
        ]);
    }

    public function test_supplier_names_must_be_unique(): void
    {
        Supplier::create(['name' => 'Same Name Supplier', 'credit_terms_days' => 60]);

        $this->actingAs($this->secretary())
            ->post('/suppliers', ['name' => 'Same Name Supplier', 'credit_terms_days' => 60])
            ->assertSessionHasErrors('name');
    }

    public function test_supplier_without_unpaid_balance_can_be_archived(): void
    {
        $supplier = Supplier::create(['name' => 'Paid Up Supplier', 'credit_terms_days' => 60]);

        $this->actingAs($this->secretary())
            ->patch(route('suppliers.archive', $supplier))
            ->assertSessionHasNoErrors();

        $this->assertSame('archived', $supplier->fresh()->status);
    }

    public function test_supplier_with_unpaid_invoice_cannot_be_archived(): void
    {
        $user = $this->secretary();
        $supplier = Supplier::create(['name' => 'Owed Supplier', 'credit_terms_days' => 60]);

        SupplierInvoice::create([
            'supplier_id'  => $supplier->id,
            'invoice_no'   => 'INV-0001',
            'invoice_date' => today(),
            'amount'       => 1000,
            'due_date'     => today()->addDays(60),
            'balance'      => 1000,
            'status'       => 'unpaid',
            'recorded_by'  => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('suppliers.archive', $supplier))
            ->assertSessionHasErrors('archive');

        $this->assertSame('active', $supplier->fresh()->status);
    }

    public function test_archived_supplier_cannot_receive_new_invoices(): void
    {
        $supplier = Supplier::create([
            'name' => 'Old Supplier', 'credit_terms_days' => 60, 'status' => 'archived',
        ]);

        $this->actingAs($this->secretary())
            ->post('/payables', [
                'supplier_id'  => $supplier->id,
                'invoice_no'   => 'INV-0002',
                'invoice_date' => today()->toDateString(),
                'amount'       => 500,
            ])
            ->assertSessionHasErrors('supplier_id');
    }
}