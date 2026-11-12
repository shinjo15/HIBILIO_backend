<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('routine_execution_draft_tags');
        Schema::dropIfExists('routine_execution_draft_actions');
        Schema::dropIfExists('routine_execution_drafts');
    }

    public function down(): void
    {
        // Removed snapshot data cannot be restored.
    }
};
