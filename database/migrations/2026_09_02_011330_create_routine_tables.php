<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table): void {
            $table->uuid('routine_identifier')->primary();
            $table->string('routine_name', 50);
            $table->string('routine_memo', 300)->nullable();
            $table->uuid('account_identifier')->index();
            $table->unsignedInteger('routine_execution_minutes')->nullable();
            $table->boolean('available')->default(true);
            $table->timestamps();
        });

        Schema::create('routine_actions', function (Blueprint $table): void {
            $table->uuid('routine_action_identifier');
            $table->uuid('parent_routine_action_identifier')->nullable()->index();
            $table->uuid('routine_identifier')->index();
            $table->string('action_name', 50);
            $table->string('action_memo', 300)->nullable();
            $table->unsignedInteger('action_minutes')->nullable();
            $table->boolean('available')->default(true);
            $table->timestamps();

            $table->primary(['routine_action_identifier']);
            $table
                ->foreign('parent_routine_action_identifier')
                ->references('routine_action_identifier')
                ->on('routine_actions')
                ->nullOnDelete();
            $table
                ->foreign('routine_identifier')
                ->references('routine_identifier')
                ->on('routines')
                ->cascadeOnDelete();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->uuid('tag_identifier')->primary();
            $table->string('tag_name', 50);
            $table->boolean('available')->default(true);
            $table->timestamps();
        });

        Schema::create('routine_tags', function (Blueprint $table): void {
            $table->uuid('routine_identifier');
            $table->uuid('tag_identifier');
            $table->boolean('available')->default(true);
            $table->timestamps();

            $table
                ->foreign('routine_identifier')
                ->references('routine_identifier')
                ->on('routines')
                ->cascadeOnDelete();
            $table
                ->foreign('tag_identifier')
                ->references('tag_identifier')
                ->on('tags')
                ->cascadeOnDelete();
            $table->primary(['routine_identifier', 'tag_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_tags');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('routine_actions');
        Schema::dropIfExists('routines');
    }
};
