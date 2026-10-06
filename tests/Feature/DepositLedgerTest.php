<?php
namespace Tests\Feature;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
class DepositLedgerTest extends FinancialStatementTest
{
    public function test_both_categories_share_summary_without_netting_movements(): void
    {
        $general=ExpenseCategory::create(['name'=>'預り金','sort_order'=>1]);
        $resident=ExpenseCategory::create(['name'=>'市県民税預り金','sort_order'=>2]);
        foreach ([[$general,'credit',1000],[$general,'debit',600],[$resident,'credit',3000]] as [$category,$side,$amount]) {
            $entry=JournalEntry::create(['entry_date'=>'2025-01-31']);
            $entry->lines()->create(['expense_category_id'=>$category->id,'side'=>$side,'amount'=>$amount]);
        }
        foreach ([$general,$resident] as $category) {
            $this->get('/general-ledger?year=2025&category_id='.$category->id)->assertOk()->assertSee('預り金・市県民税預り金')->assertSee('1,000')->assertSee('600')->assertSee('3,000')->assertViewHas('ledger',fn($ledger)=>$ledger['deposit_ledgers']->count()===2 && $ledger['deposit_ledgers'][0]['closing']==-400);
        }
    }
}
