<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routine_execution_drafts', function (Blueprint $table): void {
            $table->string('routine_execution_memo', 31)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('routine_execution_drafts', function (Blueprint $table): void {
            $table->dropColumn('routine_execution_memo');
        });
    }
};
