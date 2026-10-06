<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            // Only remove journals generated from the expense records being retired.
            DB::table('expenses')->orderBy('id')->chunkById(100, function ($expenses) {
                $markers = $expenses->map(fn ($expense) => '支出:expense_'.$expense->id)->all();
                $ids = DB::table('journal_entries')->whereIn('description', $markers)->pluck('id');
                DB::transaction(function () use ($ids) {
                    DB::table('journal_entry_lines')->whereIn('journal_entry_id', $ids)->delete();
                    DB::table('journal_entries')->whereIn('id', $ids)->delete();
                });
            });
            Schema::drop('expenses');
        }
    }

    public function down(): void
    {
        // Recreate the former schema only. Deleted trial records require a backup restore.
        if (! Schema::hasTable('expenses')) {
            (require __DIR__.'/2026_09_11_062617_create_expenses_table.php')->up();
        }
    }
};
