<?php
namespace Tests\Feature;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
class LedgerEditLinkTest extends FinancialStatementTest
{
    public function test_ledger_rows_link_to_their_own_voucher(): void
    {
        $cash=ExpenseCategory::create(['name'=>'現金','sort_order'=>0]);
        $bank=ExpenseCategory::create(['name'=>'銀行預金','sort_order'=>1]);
        foreach (['雑費','未払金','源泉所得税預り金','預り金','市県民税預り金'] as $name) {
            $category=ExpenseCategory::create(['name'=>$name,'sort_order'=>2]);
            $entry=JournalEntry::create(['entry_date'=>'2025-01-15']);
            foreach([[$category,'debit',200],[$cash,'credit',100],[$bank,'credit',100]] as [$account,$side,$amount]) $entry->lines()->create(['expense_category_id'=>$account->id,'side'=>$side,'amount'=>$amount]);
            $link=route('journal-entries.edit',$entry);
            $this->get('/general-ledger?year=2025&category_id='.$category->id)->assertOk()->assertSee('href="'.$link.'?return_to=',false);
            $this->get('/cash-book?'.http_build_query(['type'=>'現金','month'=>'2025-01']))->assertOk()->assertSee('href="'.$link.'?return_to=',false);
            $this->get('/cash-book?'.http_build_query(['type'=>'銀行預金','year'=>'2025']))->assertOk()->assertSee('href="'.$link.'?return_to=',false);
        }
        $this->assertStringNotContainsString('href=',view('general_ledger.edit_link',['row'=>['entry_id'=>$entry->id,'date'=>$entry->entry_date]])->render());
    }
}
