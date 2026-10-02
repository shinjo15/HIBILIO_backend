<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->uuid('contact_identifier')->primary();
            $table->uuid('account_identifier');
            $table->string('title', 50);
            $table->string('content', 500);
            $table->string('status', 10);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
