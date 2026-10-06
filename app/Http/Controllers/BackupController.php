<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    // The disk and folder where spatie/laravel-backup saves the .zip files
    // (storage/app/private/<APP_NAME>/ by default)
    private function disk()
    {
        return Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
    }

    private function folder(): string
    {
        return config('backup.backup.name');
    }

    public function index()
    {
        $backups = collect($this->disk()->files($this->folder()))
            ->filter(fn ($path) => str_ends_with($path, '.zip'))
            ->map(fn ($path) => [
                'name' => basename($path),
                'size' => $this->disk()->size($path),
                'date' => Carbon::createFromTimestamp($this->disk()->lastModified($path), config('app.timezone')),
            ])
            ->sortByDesc('date')
            ->values();

        $latest = $backups->first();

        return view('backups.index', [
            'backups'   => $backups,
            'latest'    => $latest,
            'totalSize' => $backups->sum('size'),
            // Warn the Operational Manager if there is no backup in the last 24 hours
            'isStale'   => ! $latest || $latest['date']->lt(now()->subDay()),
        ]);
    }

    public function store()
    {
        $this->ensureWindowsSystemRoot();

        // Same as typing "php artisan backup:run --only-db" in the terminal
        $exitCode = Artisan::call('backup:run', [
            '--only-db'               => true,
            '--disable-notifications' => true,
        ]);

        if ($exitCode !== 0) {
            // Save the full command output to storage/logs/laravel.log so the cause can be checked
            Log::error('Backup failed', ['output' => Artisan::output()]);

            return back()->withErrors([
                'backup' => 'Backup failed. Make sure MySQL is running in XAMPP and DB_DUMP_BINARY_PATH in .env is correct. Details were saved in storage/logs/laravel.log.',
            ]);
        }

        $this->log('backup');

        return back()->with('success', 'Backup created successfully.');
    }

    public function download(string $file)
    {
        $path = $this->folder() . '/' . $file;

        abort_unless($this->disk()->exists($path), 404);

        $this->log('download');

        return $this->disk()->download($path);
    }

    /**
     * When Laravel runs mysqldump from a web request on Windows, only the settings
     * found in $_SERVER / $_ENV are passed to it. Under a web server, $_SERVER holds
     * request data instead of Windows settings, so SystemRoot goes missing and
     * mysqldump fails with error 2004: "Can't create TCP/IP socket (10106)".
     * Copying the needed Windows settings into $_ENV makes sure they are passed along.
     */
    private function ensureWindowsSystemRoot(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return;
        }

        foreach (['SystemRoot', 'WINDIR', 'TEMP', 'TMP', 'PATH'] as $key) {
            $value = getenv($key);

            if ($value !== false && ! isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }

        // Fallback in case Windows did not provide it at all
        $_ENV['SystemRoot'] ??= 'C:\\Windows';
    }

    private function log(string $action): void
    {
        AuditTrail::create([
            'user_id'        => auth()->id(),
            'action'         => $action,
            'table_affected' => 'backups',
            'record_id'      => null,
        ]);
    }
}