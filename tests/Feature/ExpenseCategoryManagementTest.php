<?php
namespace Tests\Feature;
use App\Models\ExpenseCategory;
use App\Models\OpeningBalance;
class ExpenseCategoryManagementTest extends FinancialStatementTest
{
    public function test_management_creation_edit_and_used_category_protection(): void
    {
        $this->get('/expense-categories')->assertOk()->assertSee('勘定科目管理');
        $this->post('/expense-categories',['name'=>'売掛金'])->assertSessionHasNoErrors();
        $category=ExpenseCategory::where('name','売掛金')->firstOrFail();
        $this->assertSame('asset',$category->statement_type);
        $this->post('/expense-categories',['name'=>' 売掛金 '])->assertSessionHasErrors('name');
        $this->put('/expense-categories/'.$category->id,['name'=>'得意先売掛金','sort_order'=>2])->assertSessionHasNoErrors();
        $this->assertSame('asset',$category->fresh()->statement_type);
        OpeningBalance::create(['expense_category_id'=>$category->id,'effective_date'=>'2025-01-01','amount'=>100]);
        $this->delete('/expense-categories/'.$category->id)->assertSessionHasErrors('category');
        $this->put('/expense-categories/'.$category->id,['name'=>'変更'])->assertSessionHasErrors('name');
        $unused=ExpenseCategory::create(['name'=>'未使用科目','sort_order'=>100]);
        $this->delete('/expense-categories/'.$unused->id)->assertSessionHasNoErrors();
        $this->assertNull($unused->fresh());
        $system=ExpenseCategory::create(['name'=>'現金','sort_order'=>0]);
        $this->delete('/expense-categories/'.$system->id)->assertSessionHasErrors('category');
    }
}
