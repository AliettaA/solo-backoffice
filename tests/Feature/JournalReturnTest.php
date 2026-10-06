<?php
namespace Tests\Feature;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
class JournalReturnTest extends JournalEntryMonthTest
{
    public function test_save_returns_to_origin_and_rejects_external_targets(): void
    {
        $category=ExpenseCategory::create(['name'=>'現金','sort_order'=>0]);
        $entry=JournalEntry::create(['entry_date'=>'2025-01-01']);
        $data=['target_month'=>'2025-01','entry_date'=>'2025-01-02','lines'=>[
            ['side'=>'debit','expense_category_id'=>$category->id,'amount'=>100],
            ['side'=>'credit','expense_category_id'=>$category->id,'amount'=>100],
        ]];
        foreach(['/general-ledger?year=2025&category_id='.$category->id,'/cash-book?'.http_build_query(['type'=>'現金','month'=>'2025-01']),'/cash-book?'.http_build_query(['type'=>'銀行預金','year'=>'2025'])] as $url){
            $this->get('/journal-entries/'.$entry->id.'/edit?'.http_build_query(['return_to'=>$url]))->assertOk()->assertViewHas('returnTo',$url);
            $this->put('/journal-entries/'.$entry->id,$data+['return_to'=>$url])->assertRedirect($url);
        }
        foreach(['https://example.com','//example.com','/employees',"/cash-book\r\nLocation: https://example.com"] as $url){
            $this->put('/journal-entries/'.$entry->id,$data+['return_to'=>$url])->assertRedirect('/journal-entries?month=2025-01');
        }
    }
}
