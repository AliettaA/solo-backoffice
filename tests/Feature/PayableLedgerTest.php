<?php
namespace Tests\Feature;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
class PayableLedgerTest extends FinancialStatementTest
{
    public function test_payable_entries_are_split_by_side_without_mixing_totals(): void
    {
        $category=ExpenseCategory::create(['name'=>'未払金','sort_order'=>0]);
        $cash=ExpenseCategory::create(['name'=>'現金','sort_order'=>1]);
        foreach ([['2025-01-08','debit',24517],['2025-12-31','credit',26810],['2026-01-01','debit',999]] as [$date,$side,$amount]) {
            $entry=JournalEntry::create(['entry_date'=>$date]);
            $entry->lines()->create(['expense_category_id'=>$category->id,'side'=>$side,'amount'=>$amount]);
            $entry->lines()->create(['expense_category_id'=>$cash->id,'side'=>$side==='debit'?'credit':'debit','amount'=>$amount]);
        }
        $this->get('/general-ledger?year=2025&category_id='.$category->id)->assertOk()->assertSee('24,517')->assertSee('26,810')->assertViewHas('ledger',function($ledger){
            return count($ledger['payable_sections'][0]['rows'])===1 && $ledger['payable_sections'][0]['total']==24517 && $ledger['payable_sections'][1]['total']==26810;
        });
        $this->get('/general-ledger?year=2025&category_id='.$cash->id)->assertOk()->assertViewHas('ledger',fn($ledger)=>$ledger['payable_sections']===null);
        $this->get('/general-ledger?year=2024&category_id='.$category->id)->assertOk()->assertSee('該当する明細はありません。');
    }
}
