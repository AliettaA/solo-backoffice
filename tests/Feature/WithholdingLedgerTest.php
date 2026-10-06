<?php
namespace Tests\Feature;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
class WithholdingLedgerTest extends FinancialStatementTest
{
    public function test_withholding_groups_and_settlement_are_not_duplicated(): void
    {
        $tax=ExpenseCategory::create(['name'=>'源泉所得税預り金','sort_order'=>0]);
        foreach ([['労務費','credit',36780],['雑費','credit',7855],['現金','debit',44665]] as [$name,$side,$amount]) {
            $category=ExpenseCategory::create(['name'=>$name,'sort_order'=>1]);
            $entry=JournalEntry::create(['entry_date'=>'2025-06-01']);
            $entry->lines()->create(['expense_category_id'=>$tax->id,'side'=>$side,'amount'=>$amount]);
            $entry->lines()->create(['expense_category_id'=>$category->id,'side'=>$side==='credit'?'debit':'credit','amount'=>$amount]);
        }
        $this->get('/general-ledger?year=2025&category_id='.$tax->id)->assertOk()->assertViewHas('ledger',function($ledger){
            return array_column($ledger['withholding_sections'],'total')===[36780,7855,44665] && $ledger['closing']==30;
        });
        $entry=JournalEntry::create(['entry_date'=>'2025-12-31']);
        $entry->lines()->create(['expense_category_id'=>$tax->id,'side'=>'credit','amount'=>100]);
        foreach(['労務費','雑費'] as $name) $entry->lines()->create(['expense_category_id'=>ExpenseCategory::where('name',$name)->value('id'),'side'=>'debit','amount'=>50]);
        $this->get('/general-ledger?year=2025&category_id='.$tax->id)->assertOk()->assertViewHas('ledger',fn($ledger)=>array_column($ledger['withholding_sections'],'total')===[36780,7855,44665,100]);
    }
}
