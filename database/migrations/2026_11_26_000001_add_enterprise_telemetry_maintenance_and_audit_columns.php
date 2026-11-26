<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                if (! Schema::hasColumn('clients', 'low_balance_threshold')) {
                    $table->decimal('low_balance_threshold', 12, 2)->nullable()->default(5000.00);
                }
                if (! Schema::hasColumn('clients', 'low_balance_notified_at')) {
                    $table->timestamp('low_balance_notified_at')->nullable();
                }
                if (! Schema::hasColumn('clients', 'monthly_maintenance_fee')) {
                    $table->decimal('monthly_maintenance_fee', 12, 2)->nullable()->default(159500.00);
                }
                if (! Schema::hasColumn('clients', 'maintenance_enabled')) {
                    $table->boolean('maintenance_enabled')->default(true);
                }
                if (! Schema::hasColumn('clients', 'last_maintenance_billed_at')) {
                    $table->timestamp('last_maintenance_billed_at')->nullable();
                }
                if (! Schema::hasColumn('clients', 'kyc_status')) {
                    $table->string('kyc_status', 30)->default('unverified');
                }
                if (! Schema::hasColumn('clients', 'kyc_notes')) {
                    $table->text('kyc_notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('ussd_logs')) {
            Schema::table('ussd_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('ussd_logs', 'duration_ms')) {
                    $table->unsignedInteger('duration_ms')->nullable();
                }
                if (! Schema::hasColumn('ussd_logs', 'hops')) {
                    $table->unsignedInteger('hops')->nullable()->default(1);
                }
                if (! Schema::hasColumn('ussd_logs', 'network_name')) {
                    $table->string('network_name', 50)->nullable();
                }
                if (! Schema::hasColumn('ussd_logs', 'upstream_cost')) {
                    $table->decimal('upstream_cost', 10, 4)->nullable()->default(0);
                }
            });
        }

        if (! Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('actor_id')->nullable();
                $table->unsignedInteger('target_client_id')->nullable();
                $table->string('action', 100);
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('actor_id')->references('id')->on('clients')->nullOnDelete();
                $table->foreign('target_client_id')->references('id')->on('clients')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('provider', 50);
                $table->string('endpoint', 120);
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->string('outcome', 50)->default('ok');
                $table->text('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('admin_audit_logs');

        if (Schema::hasTable('ussd_logs')) {
            Schema::table('ussd_logs', function (Blueprint $table) {
                foreach (['duration_ms', 'hops', 'network_name', 'upstream_cost'] as $col) {
                    if (Schema::hasColumn('ussd_logs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                foreach ([
                    'low_balance_threshold',
                    'low_balance_notified_at',
                    'monthly_maintenance_fee',
                    'maintenance_enabled',
                    'last_maintenance_billed_at',
                    'kyc_status',
                    'kyc_notes',
                ] as $col) {
                    if (Schema::hasColumn('clients', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
