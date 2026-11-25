<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pricing_tiers')) {
            Schema::create('pricing_tiers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('tier_name')->unique();
                $table->text('description')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};
