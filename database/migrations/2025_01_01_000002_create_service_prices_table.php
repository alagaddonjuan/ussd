<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_prices')) {
            Schema::create('service_prices', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('pricing_tier_id');
                $table->enum('service_name', ['SMS', 'USSD', 'AIRTIME'])->nullable();
                $table->decimal('price', 10, 2);

                $table->foreign('pricing_tier_id')
                    ->references('id')
                    ->on('pricing_tiers')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
