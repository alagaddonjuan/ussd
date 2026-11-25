<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ussd_pricing')) {
            Schema::create('ussd_pricing', function (Blueprint $table) {
                $table->increments('id');
                $table->string('network_code')->unique('ussd_pricing_network_code_unique');
                $table->string('network_name');
                $table->integer('tokens_per_interval');
            });
        }

        if (! Schema::hasTable('ussd_menus')) {
            Schema::create('ussd_menus', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->string('menu_name');
                $table->boolean('is_active')->nullable()->default(false);
                $table->boolean('is_archived')->nullable()->default(false);

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('ussd_menu_items')) {
            Schema::create('ussd_menu_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('menu_id');
                $table->unsignedInteger('parent_item_id')->nullable();
                $table->string('option_trigger');
                $table->enum('response_type', ['CON', 'END', 'API']);
                $table->text('response_text');

                $table->foreign('menu_id')
                    ->references('id')
                    ->on('ussd_menus')
                    ->onDelete('cascade');
                $table->foreign('parent_item_id')
                    ->references('id')
                    ->on('ussd_menu_items')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_menu_items');
        Schema::dropIfExists('ussd_menus');
        Schema::dropIfExists('ussd_pricing');
    }
};
