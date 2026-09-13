<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_login_connections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('account_identifier');
            $table->string('provider', 16);
            $table->string('provider_user_identifier');
            $table->timestamps();

            $table->foreign('account_identifier')
                ->references('account_identifier')
                ->on('accounts')
                ->cascadeOnDelete();
            $table->unique(['provider', 'provider_user_identifier']);
            $table->unique(['account_identifier', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_login_connections');
    }
};
