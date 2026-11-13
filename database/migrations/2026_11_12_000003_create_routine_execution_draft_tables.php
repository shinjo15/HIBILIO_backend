<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_execution_drafts', function (Blueprint $table): void {
            $table->uuid('routine_execution_identifier')->primary();
            $table->uuid('executor_account_identifier')->index();
            $table->uuid('routine_identifier')->index();
            $table->dateTime('executed_at');
            $table->string('routine_execution_memo', 31)->nullable();
            $table->timestamps();
            $table->unique(['executor_account_identifier', 'routine_identifier'], 'execution_drafts_account_routine_unique');
            $table->foreign('executor_account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
            $table->foreign('routine_identifier')->references('routine_identifier')->on('routines')->cascadeOnDelete();
        });

        Schema::create('routine_execution_action_drafts', function (Blueprint $table): void {
            $table->uuid('routine_execution_identifier');
            $table->uuid('routine_action_identifier');
            $table->timestamps();
            $table->primary(['routine_execution_identifier', 'routine_action_identifier'], 'execution_action_drafts_primary');
            $table->foreign('routine_execution_identifier', 'execution_action_drafts_execution_fk')->references('routine_execution_identifier')->on('routine_execution_drafts')->cascadeOnDelete();
            $table->foreign('routine_action_identifier', 'execution_action_drafts_action_fk')->references('routine_action_identifier')->on('routine_actions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_execution_action_drafts');
        Schema::dropIfExists('routine_execution_drafts');
    }
};
