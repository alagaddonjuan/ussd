<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_keys')) {
            Schema::create('api_keys', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->string('key_name');
                $table->string('api_key')->unique();
                $table->text('whitelisted_ips')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
