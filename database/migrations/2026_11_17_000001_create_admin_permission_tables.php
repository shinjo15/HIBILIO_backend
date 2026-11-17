<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('functions', function (Blueprint $table): void {
            $table->uuid('function_identifier')->primary();
            $table->longText('function_name');
        });

        Schema::create('permission_groups', function (Blueprint $table): void {
            $table->uuid('permission_group_identifier')->primary();
            $table->string('permission_group_name', 50);
        });

        Schema::create('admin_permission_group', function (Blueprint $table): void {
            $table->uuid('admin_identifier');
            $table->uuid('permission_group_identifier');
            $table->primary(['admin_identifier', 'permission_group_identifier'], 'admin_group_primary');
            $table->foreign('admin_identifier')->references('admin_identifier')->on('admins');
            $table->foreign('permission_group_identifier', 'admin_group_group_foreign')
                ->references('permission_group_identifier')->on('permission_groups');
        });

        Schema::create('function_permission_group', function (Blueprint $table): void {
            $table->uuid('function_identifier');
            $table->uuid('permission_group_identifier');
            $table->primary(['function_identifier', 'permission_group_identifier'], 'function_group_primary');
            $table->foreign('function_identifier')->references('function_identifier')->on('functions');
            $table->foreign('permission_group_identifier', 'function_group_group_foreign')
                ->references('permission_group_identifier')->on('permission_groups');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('function_permission_group');
        Schema::dropIfExists('admin_permission_group');
        Schema::dropIfExists('permission_groups');
        Schema::dropIfExists('functions');
    }
};
