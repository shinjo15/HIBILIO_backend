<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persistent_login_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('selector')->unique();
            $table->uuid('account_identifier');
            $table->string('validator_hash');
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->foreign('account_identifier')
                ->references('account_identifier')
                ->on('accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persistent_login_tokens');
    }
};
