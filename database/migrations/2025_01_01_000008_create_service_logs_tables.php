<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->string('sender_id', 50)->nullable();
                $table->string('phone_number', 20)->nullable();
                $table->text('message')->nullable();
                $table->decimal('cost', 10, 2)->nullable()->default(0.00);
                $table->string('status', 50)->nullable()->default('Sent');
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('airtime_logs')) {
            Schema::create('airtime_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->string('phone_number', 20)->nullable();
                $table->string('network', 50)->nullable();
                $table->decimal('amount', 10, 2)->nullable()->default(0.00);
                $table->string('status', 50)->nullable()->default('Success');
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('ussd_logs')) {
            Schema::create('ussd_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->string('phone_number', 20)->nullable();
                $table->string('session_id', 100)->nullable();
                $table->string('service_code', 50)->nullable();
                $table->text('session_data')->nullable();
                $table->decimal('cost', 10, 2)->nullable()->default(0.00);
                $table->string('status', 50)->nullable()->default('Success');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('logged_at')->nullable()->useCurrent();
                $table->decimal('client_price', 10, 2)->nullable()->default(0.00);
                $table->text('final_user_string')->nullable();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_logs');
        Schema::dropIfExists('airtime_logs');
        Schema::dropIfExists('sms_logs');
    }
};
