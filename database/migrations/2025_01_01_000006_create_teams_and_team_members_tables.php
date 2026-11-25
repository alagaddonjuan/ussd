<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('client_id');
                $table->string('team_name');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('team_members')) {
            Schema::create('team_members', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('team_id');
                $table->unsignedInteger('client_id');
                $table->unsignedInteger('role_id');
                $table->enum('status', ['pending', 'active'])->default('pending');
                $table->string('invitation_token')->nullable()->unique();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('team_id')
                    ->references('id')
                    ->on('teams')
                    ->onDelete('cascade');
                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
