<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('email')->unique('clients_email_unique');
                $table->string('password');
                $table->boolean('is_2fa_enabled')->default(false);
                $table->string('two_fa_secret')->nullable();
                $table->string('ussd_code')->nullable()->unique('clients_ussd_code_unique');
                $table->decimal('token_balance', 14, 2)->default(0);
                $table->string('sender_id')->nullable();
                $table->boolean('is_admin')->nullable()->default(false);
                $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
                $table->unsignedInteger('pricing_tier_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->string('password_reset_token')->nullable();
                $table->timestamp('password_reset_expires')->nullable();

                $table->foreign('pricing_tier_id')
                    ->references('id')
                    ->on('pricing_tiers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
