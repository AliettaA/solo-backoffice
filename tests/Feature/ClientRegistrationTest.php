<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
class ClientRegistrationTest extends TestCase
{
    public function test_registration_number_can_be_saved_edited_cleared_and_validated(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        foreach (['*create_clients_table.php', '*add_invoice_registration_number_to_clients.php'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern)) as $file) (require $file)->up();
        }
        $this->post('/clients', ['name' => 'テスト', 'invoice_registration_number' => 'T1234567890123'])->assertSessionHasNoErrors();
        $client = Client::firstOrFail();
        $this->assertSame('T1234567890123', $client->invoice_registration_number);
        $this->get('/clients')->assertOk()->assertSee('T1234567890123');
        $this->get('/clients/'.$client->id.'/edit')->assertOk()->assertSee('T1234567890123');
        $this->put('/clients/'.$client->id, ['name' => 'テスト', 'invoice_registration_number' => 'T0000000000001'])->assertSessionHasNoErrors();
        $this->assertSame('T0000000000001', $client->fresh()->invoice_registration_number);
        $this->put('/clients/'.$client->id, ['name' => 'テスト', 'invoice_registration_number' => 'T123'])->assertSessionHasErrors('invoice_registration_number');
        $this->assertSame('T0000000000001', $client->fresh()->invoice_registration_number);
        $this->put('/clients/'.$client->id, ['name' => 'テスト', 'invoice_registration_number' => ''])->assertSessionHasNoErrors();
        $this->assertNull($client->fresh()->invoice_registration_number);
    }
}
