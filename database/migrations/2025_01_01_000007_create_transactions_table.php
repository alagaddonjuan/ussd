<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id')->nullable();
                $table->enum('type', ['credit', 'debit'])->default('credit');
                $table->string('reference')->unique('transactions_reference_unique');
                $table->decimal('amount', 14, 2);
                $table->integer('tokens_purchased');
                $table->string('status')->nullable()->default('Pending');
                $table->string('gateway')->nullable()->default('paystack');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
