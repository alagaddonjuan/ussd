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
                if (! Schema::hasColumn('clients', 'kyc_document_path')) {
                    $table->string('kyc_document_path', 255)->nullable();
                }
                if (! Schema::hasColumn('clients', 'kyc_submitted_at')) {
                    $table->timestamp('kyc_submitted_at')->nullable();
                }
                if (! Schema::hasColumn('clients', 'perfex_client_id')) {
                    $table->unsignedInteger('perfex_client_id')->nullable();
                }
                if (! Schema::hasColumn('clients', 'maintenance_reminded_at')) {
                    $table->timestamp('maintenance_reminded_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('transactions', 'perfex_invoice_id')) {
                    $table->string('perfex_invoice_id', 64)->nullable();
                }
            });
        }

        if (! Schema::hasTable('ussd_menu_versions')) {
            Schema::create('ussd_menu_versions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('menu_id');
                $table->unsignedInteger('version_number')->default(1);
                $table->string('label', 120)->nullable();
                $table->longText('outline_text')->nullable();
                $table->longText('snapshot_json');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('menu_id')->references('id')->on('ussd_menus')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('clients')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_menu_versions');

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'perfex_invoice_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('perfex_invoice_id');
            });
        }

        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                foreach (['kyc_document_path', 'kyc_submitted_at', 'perfex_client_id', 'maintenance_reminded_at'] as $col) {
                    if (Schema::hasColumn('clients', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
