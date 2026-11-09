<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_notifications', function (Blueprint $table): void {
            $table->uuid('notification_identifier')->primary();
            $table->uuid('receiver_account_identifier')->index();
            $table->text('content');
            $table->string('status', 10);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('receiver_account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
            $table->index(['receiver_account_identifier', 'created_at', 'notification_identifier']);
        });

        Schema::create('system_announcements', function (Blueprint $table): void {
            $table->uuid('announcement_identifier')->primary();
            $table->text('title');
            $table->text('content');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index(['created_at', 'announcement_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_announcements');
        Schema::dropIfExists('personal_notifications');
    }
};
