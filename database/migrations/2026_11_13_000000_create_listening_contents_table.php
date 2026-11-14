<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_contents', function (Blueprint $table): void {
            $table->uuid('listening_content_identifier')->primary();
            $table->uuid('account_identifier');
            $table->longText('url');
            $table->timestamps();
            $table->foreign('account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_contents');
    }
};
