<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('routine_actions', 'position')) {
            Schema::table('routine_actions', function (Blueprint $table): void {
                $table->unsignedInteger('position')->nullable();
            });
        }

        DB::statement(<<<'SQL'
            WITH ranked_actions AS (
                SELECT routine_action_identifier,
                    ROW_NUMBER() OVER (
                        PARTITION BY routine_identifier
                        ORDER BY CASE WHEN created_at IS NULL THEN 1 ELSE 0 END,
                            created_at, routine_action_identifier
                    ) - 1 AS position
                FROM routine_actions
            )
            UPDATE routine_actions
            SET position = ranked_actions.position
            FROM ranked_actions
            WHERE routine_actions.routine_action_identifier = ranked_actions.routine_action_identifier
                AND routine_actions.position IS NULL
            SQL);

        Schema::table('routine_actions', function (Blueprint $table): void {
            $table->unsignedInteger('position')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('routine_actions', function (Blueprint $table): void {
            $table->dropColumn('position');
        });
    }
};
