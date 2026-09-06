<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_executions', function (Blueprint $table): void {
            $table->uuid('routine_execution_identifier')->primary();
            $table->uuid('executor_account_identifier')->index();
            $table->uuid('routine_identifier')->index();
            $table->dateTime('executed_at');
            $table->string('routine_execution_memo', 31)->nullable();
            $table->timestamps();

            $table
                ->foreign('executor_account_identifier')
                ->references('account_identifier')
                ->on('accounts')
                ->cascadeOnDelete();
            $table
                ->foreign('routine_identifier')
                ->references('routine_identifier')
                ->on('routines')
                ->cascadeOnDelete();
        });

        Schema::create('routine_execution_actions', function (Blueprint $table): void {
            $table->uuid('routine_execution_identifier');
            $table->uuid('routine_action_identifier');
            $table->timestamps();

            $table->primary([
                'routine_execution_identifier',
                'routine_action_identifier',
            ]);
            $table
                ->foreign('routine_execution_identifier')
                ->references('routine_execution_identifier')
                ->on('routine_executions')
                ->cascadeOnDelete();
            $table
                ->foreign('routine_action_identifier')
                ->references('routine_action_identifier')
                ->on('routine_actions')
                ->cascadeOnDelete();
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table
                ->uuid('routine_execution_identifier')
                ->nullable()
                ->index()
                ->after('routine_identifier');
            $table
                ->foreign('routine_execution_identifier')
                ->references('routine_execution_identifier')
                ->on('routine_executions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropForeign(['routine_execution_identifier']);
            $table->dropColumn('routine_execution_identifier');
        });

        Schema::dropIfExists('routine_execution_actions');
        Schema::dropIfExists('routine_executions');
    }
};
