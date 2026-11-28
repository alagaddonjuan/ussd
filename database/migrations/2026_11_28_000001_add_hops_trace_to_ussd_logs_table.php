<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ussd_logs') && ! Schema::hasColumn('ussd_logs', 'hops_trace')) {
            Schema::table('ussd_logs', function (Blueprint $table) {
                $table->longText('hops_trace')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ussd_logs') && Schema::hasColumn('ussd_logs', 'hops_trace')) {
            Schema::table('ussd_logs', function (Blueprint $table) {
                $table->dropColumn('hops_trace');
            });
        }
    }
};
