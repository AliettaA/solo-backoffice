<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalEntryMonthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        foreach (['*create_expense_categories*', '*create_journal_entries*', '*create_journal_entry_lines*', '*create_payees*'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern.'.php')) as $path) {
                (require $path)->up();
            }
        }
    }

    public function test_invalid_categories_and_unbalanced_entries_are_rejected(): void
    {
        foreach (['', 999999, 'other'] as $category) {
            $data = $this->payload('2025-01', '2025-01-01');
            $data['lines'][0]['expense_category_id'] = $category;
            $key = $category === 'other' ? 'lines.0.new_category_name' : 'lines.0.expense_category_id';
            $this->post('/journal-entries', $data)->assertSessionHasErrors($key);
        }
        $data = $this->payload('2025-01', '2025-01-01');
        $data['lines'][0]['amount'] = 101;
        $this->post('/journal-entries', $data)->assertSessionHasErrors('lines');
        $this->assertSame(0, JournalEntry::count());
        $data['lines'][0]['amount'] = 100;
        $data['lines'][0]['expense_category_id'] = 'other';
        $data['lines'][0]['new_category_name'] = '新科目';
        $this->post('/journal-entries', $data)->assertSessionHasNoErrors();
        $entry = JournalEntry::firstOrFail();
        $ids = $entry->lines->pluck('id')->all();
        $data['lines'][0]['expense_category_id'] = 999999;
        $this->put('/journal-entries/'.$entry->id, $data)->assertSessionHasErrors('lines.0.expense_category_id');
        $this->assertSame($ids, $entry->fresh()->lines->pluck('id')->all());
    }

    private function payload(string $month, string $date): array
    {
        $cash = ExpenseCategory::firstOrCreate(['name' => '現金'], ['sort_order' => 0]);

        return ['target_month' => $month, 'entry_date' => $date, 'description' => '入力を保持', 'lines' => [
            ['side' => 'debit', 'expense_category_id' => $cash->id, 'amount' => 100],
            ['side' => 'credit', 'expense_category_id' => $cash->id, 'amount' => 100],
        ]];
    }

    public function test_selected_month_is_used_and_saved_for_next_creation(): void
    {
        $this->get('/journal-entries/create?month=2025-02')->assertOk()->assertViewHas('month', '2025-02')->assertSee('2025-02-01');
        $this->post('/journal-entries', $this->payload('2025-02', '2025-02-28'))->assertRedirect('/journal-entries?month=2025-02');
        $this->get('/journal-entries/create')->assertViewHas('month', '2025-02');
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_wrong_month_and_invalid_day_never_save_and_keep_input(): void
    {
        foreach (['2025-03-01', '2024-02-28', '2025-02-29'] as $date) {
            $data = $this->payload('2025-02', $date);
            $this->from('/journal-entries/create?month=2025-02')->post('/journal-entries', $data)->assertSessionHasErrors('entry_date')->assertSessionHasInput('description', '入力を保持');
        }
        $this->assertSame(0, JournalEntry::count());
        $this->post('/journal-entries', $this->payload('2024-02', '2024-02-29'))->assertSessionHasNoErrors();
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_edit_uses_existing_month_and_rejects_mismatch_without_replacing_lines(): void
    {
        $this->post('/journal-entries', $this->payload('2025-01', '2025-01-31'));
        $entry = JournalEntry::firstOrFail();
        $lineIds = $entry->lines->pluck('id')->all();
        $this->get('/journal-entries/'.$entry->id.'/edit')->assertOk()->assertViewHas('month', '2025-01');
        $this->put('/journal-entries/'.$entry->id, $this->payload('2025-01', '2025-02-01'))->assertSessionHasErrors('entry_date');
        $this->assertSame('2025-01-31', $entry->fresh()->entry_date->toDateString());
        $this->assertSame($lineIds, $entry->fresh()->lines->pluck('id')->all());
        $this->put('/journal-entries/'.$entry->id, $this->payload('2025-02', '2025-02-01'))->assertSessionHasNoErrors();
        $this->assertSame('2025-02-01', $entry->fresh()->entry_date->toDateString());
    }

    public function test_month_is_required_and_query_month_is_validated(): void
    {
        $data = $this->payload('2025-01', '2025-01-01');
        unset($data['target_month']);
        $this->post('/journal-entries', $data)->assertSessionHasErrors('target_month');
        $this->get('/journal-entries/create?month=2025-13')->assertSessionHasErrors('month');
        $this->assertSame(0,JournalEntry::count());
    }
}
