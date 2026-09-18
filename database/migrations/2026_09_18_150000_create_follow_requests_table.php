<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_requests', function (Blueprint $table): void {
            $table->uuid('requesting_account_identifier');
            $table->uuid('target_account_identifier');
            $table->string('status');
            $table->timestamps();
            $table->primary(['requesting_account_identifier', 'target_account_identifier']);
            $table->foreign('requesting_account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
            $table->foreign('target_account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_requests');
    }
};
