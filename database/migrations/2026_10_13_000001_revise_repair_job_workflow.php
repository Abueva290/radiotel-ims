<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 | Panel revisions (Oct 2026): repair/service workflow, technician assignment,
 | status history and service timeframe.
 |
 | The existing statuses are kept (For Assessment, In Progress, Completed, Released).
 | Only two are added: Waiting for Parts and Cancelled.
 |
 | No history is invented for existing jobs: target dates, assignments and release
 | times stay NULL ("not recorded"). Only values that really exist are copied:
 |   received_at  <- created_at, when the job was encoded on the day it was received
 |   completed_at <- the moment the receivable was created (that is when "Completed" billed it)
 */
return new class extends Migration
{
    private const OLD_STATUSES = "'for_assessment','in_progress','completed','released'";
    private const NEW_STATUSES = "'for_assessment','in_progress','waiting_for_parts','completed','released','cancelled'";

    public function up(): void
    {
        // 1. Timeframe and current-assignment columns
        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->dateTime('received_at')->nullable()->after('date_received');
            $table->date('target_completion_date')->nullable()->after('received_at');
            $table->dateTime('started_at')->nullable()->after('target_completion_date');
            $table->dateTime('completed_at')->nullable()->after('started_at');   // work finished (status Completed; this is when the customer is billed)
            $table->dateTime('released_at')->nullable()->after('completed_at');  // unit claimed by the customer (status Released)
            $table->dateTime('cancelled_at')->nullable()->after('released_at');

            $table->foreignId('assigned_to')->nullable()->after('assessed_by')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable()->after('assigned_by');

            $table->index('status');
            $table->index('target_completion_date');
            $table->index('date_received');
        });

        // 2. Status enum: add the two new values (existing values are untouched)
        DB::statement("ALTER TABLE repair_jobs MODIFY status ENUM(" . self::NEW_STATUSES . ") NOT NULL DEFAULT 'for_assessment'");

        // 3. Copy only timestamps that really exist
        DB::statement("UPDATE repair_jobs SET received_at = created_at
            WHERE received_at IS NULL AND DATE(created_at) = date_received");
        DB::statement("UPDATE repair_jobs rj JOIN receivables r ON r.repair_job_id = rj.id
            SET rj.completed_at = r.created_at
            WHERE rj.status IN ('completed','released') AND rj.completed_at IS NULL");

        // 4. Assignment history
        Schema::create('repair_job_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('assigned_at');
            $table->date('expected_completion_date')->nullable();
            $table->dateTime('ended_at')->nullable(); // set when the job is reassigned
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->index(['repair_job_id', 'assigned_at']);
        });

        // 5. Status history (audit trail of every status change)
        Schema::create('repair_job_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->boolean('is_override')->default(false);
            $table->dateTime('changed_at');

            $table->index(['repair_job_id', 'changed_at']);
        });

        // One clearly-labelled starting entry per existing job (dated today, no user),
        // so every timeline has a beginning without pretending to know the past
        DB::table('repair_job_status_logs')->insertUsing(
            ['repair_job_id', 'from_status', 'to_status', 'changed_by', 'remarks', 'is_override', 'changed_at'],
            DB::table('repair_jobs')->selectRaw("id, NULL, status, NULL, 'Status when history tracking started. Earlier changes were not recorded.', 0, ?", [now()])
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_job_status_logs');
        Schema::dropIfExists('repair_job_assignments');

        DB::table('repair_jobs')->where('status', 'waiting_for_parts')->update(['status' => 'in_progress']);
        DB::table('repair_jobs')->where('status', 'cancelled')->update(['status' => 'for_assessment']);
        DB::statement("ALTER TABLE repair_jobs MODIFY status ENUM(" . self::OLD_STATUSES . ") NOT NULL DEFAULT 'for_assessment'");

        Schema::table('repair_jobs', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['assigned_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['target_completion_date']);
            $table->dropIndex(['date_received']);
            $table->dropColumn([
                'received_at', 'target_completion_date', 'started_at', 'completed_at',
                'released_at', 'cancelled_at', 'assigned_to', 'assigned_by', 'assigned_at',
            ]);
        });
    }
};