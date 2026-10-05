<?php

namespace App\Providers;

use App\Models\AuditTrail;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ReceivablePayment;
use App\Models\RepairJob;
use App\Models\RepairPartUsed;
use App\Models\Sale;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Tables whose changes are recorded in the audit trail.
     */
    public const AUDITED_MODELS = [
        Sale::class,
        ReceivablePayment::class,
        SupplierInvoice::class,
        SupplierPayment::class,
        Product::class,
        InventoryMovement::class,
        RepairJob::class,
        RepairPartUsed::class,
        Customer::class,
        User::class,
    ];

    /**
     * Changes to only these columns are not worth logging.
     */
    private const IGNORED_COLUMNS = ['updated_at', 'last_login_at', 'remember_token'];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (self::AUDITED_MODELS as $model) {
            $model::created(fn (Model $record) => $this->audit('created', $record));

            $model::updated(function (Model $record) {
                $changed = array_diff(array_keys($record->getChanges()), self::IGNORED_COLUMNS);
                if ($changed) {
                    $this->audit('updated', $record);
                }
            });

            $model::deleted(fn (Model $record) => $this->audit('deleted', $record));
        }

        Event::listen(Login::class, fn (Login $event) => AuditTrail::create([
            'user_id'        => $event->user->getAuthIdentifier(),
            'action'         => 'login',
            'table_affected' => 'users',
            'record_id'      => $event->user->getAuthIdentifier(),
        ]));

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditTrail::create([
                    'user_id'        => $event->user->getAuthIdentifier(),
                    'action'         => 'logout',
                    'table_affected' => 'users',
                    'record_id'      => $event->user->getAuthIdentifier(),
                ]);
            }
        });
    }

    private function audit(string $action, Model $record): void
    {
        // Skip seeders and artisan commands; only log actions done by logged-in users
        if (app()->runningInConsole() || ! auth()->check()) {
            return;
        }

        AuditTrail::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'table_affected' => $record->getTable(),
            'record_id'      => $record->getKey(),
        ]);
    }
}