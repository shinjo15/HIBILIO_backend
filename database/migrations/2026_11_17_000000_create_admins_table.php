<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table): void {
            $table->uuid('admin_identifier')->primary();
            $table->string('admin_name', 50);
            $table->string('email_address');
            $table->timestamp('create_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->uuid('create_user')->nullable();
            $table->uuid('updated_user')->nullable();
            $table->boolean('available')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
