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
            $table->uuid('owner_account_identifier');
            $table->uuid('routine_identifier');
            $table->uuid('parent_routine_identifier')->nullable();
            $table->uuid('account_identifier');
            $table->string('routine_name', 50);
            $table->string('routine_memo', 300)->nullable();
            $table->unsignedInteger('routine_execution_minutes')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->boolean('available');
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();
            $table->primary(['owner_account_identifier', 'routine_identifier'], 'execution_drafts_primary');
            $table->foreign('owner_account_identifier')->references('account_identifier')->on('accounts')->cascadeOnDelete();
        });

        Schema::create('routine_execution_draft_actions', function (Blueprint $table): void {
            $table->uuid('owner_account_identifier');
            $table->uuid('routine_identifier');
            $table->uuid('routine_action_identifier');
            $table->uuid('parent_routine_action_identifier')->nullable();
            $table->string('action_name', 50);
            $table->string('action_memo', 300)->nullable();
            $table->unsignedInteger('action_minutes')->nullable();
            $table->unsignedInteger('position');
            $table->string('video_url', 2048)->nullable();
            $table->boolean('available');
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->primary(['owner_account_identifier', 'routine_identifier', 'routine_action_identifier'], 'execution_draft_actions_primary');
            $table->foreign(['owner_account_identifier', 'routine_identifier'], 'execution_draft_actions_draft_fk')->references(['owner_account_identifier', 'routine_identifier'])->on('routine_execution_drafts')->cascadeOnDelete();
        });

        Schema::create('routine_execution_draft_tags', function (Blueprint $table): void {
            $table->uuid('owner_account_identifier');
            $table->uuid('routine_identifier');
            $table->uuid('tag_identifier');
            $table->primary(['owner_account_identifier', 'routine_identifier', 'tag_identifier'], 'execution_draft_tags_primary');
            $table->foreign(['owner_account_identifier', 'routine_identifier'], 'execution_draft_tags_draft_fk')->references(['owner_account_identifier', 'routine_identifier'])->on('routine_execution_drafts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_execution_draft_tags');
        Schema::dropIfExists('routine_execution_draft_actions');
        Schema::dropIfExists('routine_execution_drafts');
    }
};
