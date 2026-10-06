<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
class ClientPaymentJournalTest extends TestCase
{
    public function test_receipts_create_balanced_journals_without_existing_categories(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        foreach (['*create_clients_table.php','*create_lumber_invoices_table.php','*create_client_payments_table.php','*create_expense_categories_table.php','*create_journal_entries_table.php','*create_journal_entry_lines_table.php'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern)) as $file) (require $file)->up();
        }
        $client = Client::create(['name' => '入金テスト']);
        $this->get('/payments?month=2025-01')->assertOk()->assertSee('新規入金登録')->assertSee('入金テスト')->assertSee('name="client_id"', false);
        foreach (['現金' => '現金', '銀行振込' => '銀行預金'] as $method => $category) {
            $this->post('/payments', ['client_id' => $client->id, 'paid_at' => '2025-01-31', 'target_month' => '2025-01', 'amount' => 10000, 'method' => $method])->assertSessionHasNoErrors();
            $payment = ClientPayment::latest('id')->firstOrFail();
            $entry = JournalEntry::where('description', '入金:client_payment_'.$payment->id)->firstOrFail();
            $this->assertEquals('2025-01-31', $entry->entry_date->format('Y-m-d'));
            $this->assertEquals($category, $entry->lines()->where('side', 'debit')->first()->category->name);
            $this->assertEquals('売上', $entry->lines()->where('side', 'credit')->first()->category->name);
            $this->assertEquals(10000, $entry->lines()->where('side', 'debit')->sum('amount'));
            $this->assertEquals(10000, $entry->lines()->where('side', 'credit')->sum('amount'));
            $payment->syncJournalEntry();
            $this->assertEquals(1, JournalEntry::where('description', '入金:client_payment_'.$payment->id)->count());
            $this->delete('/payments/'.$payment->id)->assertSessionHasNoErrors();
            $this->assertNull($entry->fresh());
        }
    }
}
