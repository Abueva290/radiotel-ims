<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The deployment seeder creates only the admin account, with no sample data.
 */
class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    private function setInitialPassword(?string $value): void
    {
        if ($value === null) {
            putenv('ADMIN_INITIAL_PASSWORD');
            unset($_ENV['ADMIN_INITIAL_PASSWORD'], $_SERVER['ADMIN_INITIAL_PASSWORD']);
        } else {
            putenv("ADMIN_INITIAL_PASSWORD={$value}");
            $_ENV['ADMIN_INITIAL_PASSWORD'] = $_SERVER['ADMIN_INITIAL_PASSWORD'] = $value;
        }
    }

    protected function tearDown(): void
    {
        $this->setInitialPassword(null);
        parent::tearDown();
    }

    public function test_creates_only_the_admin_account_without_sample_data(): void
    {
        $this->setInitialPassword('Radiotel2026!');

        $this->seed(ProductionSeeder::class);

        $this->assertSame(1, User::count());
        $admin = User::first();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->must_change_password);

        $this->assertSame(0, Supplier::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Customer::count());
    }

    public function test_does_nothing_without_an_initial_password(): void
    {
        $this->setInitialPassword(null);

        $this->seed(ProductionSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_does_not_create_a_second_admin(): void
    {
        User::factory()->create(['role' => 'admin']);
        $this->setInitialPassword('Radiotel2026!');

        $this->seed(ProductionSeeder::class);

        $this->assertSame(1, User::count());
    }
}