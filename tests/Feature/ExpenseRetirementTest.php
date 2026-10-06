<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class ExpenseRetirementTest extends TestCase
{
    public function test_retirement_removes_only_expense_data_and_manual_journals_work(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','app.key'=>'base64:'.base64_encode(str_repeat('a',32))]);
        DB::purge('sqlite');
        foreach(['*create_expense_categories_table.php','*create_payees_table.php','*create_expenses_table.php','*create_journal_entries_table.php','*create_journal_entry_lines_table.php','*create_opening_balances_table.php'] as $pattern){
            foreach(glob(database_path('migrations/'.$pattern)) as $file)(require $file)->up();
        }
        $category=ExpenseCategory::create(['name'=>'消耗品費','sort_order'=>1]);
        $cash=ExpenseCategory::create(['name'=>'現金','sort_order'=>2]);
        $expenseId=DB::table('expenses')->insertGetId(['paid_at'=>'2025-01-15','expense_category_id'=>$category->id,'amount'=>1000,'payment_method'=>'現金']);
        $generated=JournalEntry::create(['entry_date'=>'2025-01-15','description'=>'支出:expense_'.$expenseId]);
        $generated->lines()->create(['side'=>'debit','expense_category_id'=>$category->id,'amount'=>1000,'sort_order'=>0]);
        $kept=JournalEntry::create(['entry_date'=>'2025-01-15','description'=>'給与:salary_payment_1']);
        (require database_path('migrations/2026_09_21_000002_remove_expense_management.php'))->up();
        $this->assertFalse(Schema::hasTable('expenses'));
        $this->assertNull($generated->fresh());
        $this->assertNotNull($kept->fresh());
        $this->assertEquals(2,ExpenseCategory::count());
        $this->get('/expenses')->assertNotFound();
        $this->get('/journal-entries/create')->assertOk()->assertDontSee('支出管理');
        $this->post('/journal-entries',['target_month'=>'2025-01','entry_date'=>'2025-01-31','description'=>'消耗品購入','lines'=>[
            ['side'=>'debit','expense_category_id'=>$category->id,'amount'=>1500,'counterparty_name'=>'店A'],
            ['side'=>'credit','expense_category_id'=>$cash->id,'amount'=>1500],
        ]])->assertSessionHasNoErrors();
        $this->get('/cash-book?month=2025-01')->assertOk()->assertSee('消耗品購入')->assertViewHas('book',fn($b)=>$b['expense_total']==1500);
        $this->get('/general-ledger?year=2025&category_id='.$category->id)->assertOk()->assertViewHas('ledger',fn($b)=>$b['closing']==1500);
    }
}
