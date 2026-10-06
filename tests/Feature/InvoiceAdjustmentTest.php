<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LumberInvoice;
use App\Models\LumberSpecies;
use App\Models\QuantityTierSurcharge;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceAdjustmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        foreach (['*create_clients*', '*create_products*', '*create_lumber_species*', '*create_lumber_width_prices*', '*create_thickness_surcharges*', '*create_quantity_tier_surcharges*', '*create_pricing_settings*', '*create_lumber_invoices*', '*create_lumber_invoice_lines*', '*create_lumber_invoice_line_tiers*', '*create_lumber_invoice_product_lines*', '*add_mixed_tax_columns*', '*add_invoice_adjustments*', '*add_returned_quantity*'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern.'.php')) as $path) {
                (require $path)->up();
            }
        }
    }

    private function payload(int $unit = -5, int $adjustment = -500): array
    {
        $client = Client::firstOrCreate(['name' => 'テスト取引先']);
        $species = LumberSpecies::firstOrCreate(['name' => '杉'], ['pricing_type' => 'flat', 'flat_price' => 100, 'apply_thickness_surcharge' => false, 'apply_length_adjustment' => false]);
        $tier = QuantityTierSurcharge::firstOrCreate(['label' => '20-'], ['min_qty' => 20, 'surcharge_amount' => 0, 'sort_order' => 0]);

        return ['client_id' => $client->id, 'issue_date' => '2025-01-10', 'invoice_adjustment_description' => '板買取', 'invoice_adjustment_amount' => $adjustment, 'lines' => [
            ['lumber_species_id' => $species->id, 'thickness' => 18, 'width' => 12, 'length' => 4, 'unit_price_adjustment' => $unit, 'adjustment_amount' => 0, 'quantities' => [$tier->id => 20]],
        ]];
    }

    public function test_returned_quantity_changes_count_only_and_survives_edit(): void
    {
        $data = $this->payload(0, 0);
        $data['lines'][0]['returned_quantity'] = 3;
        $this->post('/lumber-invoices', $data)->assertSessionHasNoErrors();
        $invoice = LumberInvoice::firstOrFail();
        $this->assertEquals(23, $invoice->lines->first()->total_quantity);
        $this->assertEquals(2200, $invoice->total);
        $this->get('/lumber-invoices/'.$invoice->id.'/edit')->assertOk()->assertViewHas('existingLines', fn ($lines) => $lines[0]['returned_quantity'] == 3);
        $data['lines'][0]['quantities'] = [];
        $this->put('/lumber-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(3, $invoice->lines->first()->total_quantity);
        $this->assertEquals(0, $invoice->total);
        $this->assertSame(0, $invoice->lines->first()->tiers()->count());
        foreach ([-1, 1.5, 1000001] as $invalid) {
            $data['lines'][0]['returned_quantity'] = $invalid;
            $this->put('/lumber-invoices/'.$invoice->id, $data)->assertSessionHasErrors('lines.0.returned_quantity');
        }
        $this->assertEquals(3, $invoice->fresh()->lines->first()->total_quantity);
    }

    public function test_both_adjustments_are_saved_taxed_and_restored(): void
    {
        $data = $this->payload();
        $this->post('/lumber-invoices', $data)->assertSessionHasNoErrors();
        $invoice = LumberInvoice::firstOrFail();
        $this->assertEquals(95, $invoice->lines->first()->tiers->first()->unit_price);
        $this->assertEquals(1900, $invoice->lines->first()->line_total);
        $this->assertEquals(1400, $invoice->subtotal);
        $this->assertEquals(140, $invoice->tax_amount);
        $this->assertEquals(1540, $invoice->total);
        $this->get('/lumber-invoices/'.$invoice->id.'/edit')->assertOk()->assertSee('板買取')->assertViewHas('existingLines', fn ($lines) => $lines[0]['unit_price_adjustment'] === -5);
        $data['lines'][0]['unit_price_adjustment'] = 10;
        $data['invoice_adjustment_amount'] = 500;
        $this->put('/lumber-invoices/'.$invoice->id, $data)->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertEquals(2970, $invoice->total);
        $this->assertSame(1, $invoice->lines()->count());
    }

    public function test_invalid_step_and_negative_totals_roll_back(): void
    {
        $this->post('/lumber-invoices', $this->payload(-3))->assertSessionHasErrors('lines.0.unit_price_adjustment');
        $this->post('/lumber-invoices', $this->payload(-105))->assertSessionHasErrors('lines');
        $this->post('/lumber-invoices', $this->payload(-5, -2000))->assertSessionHasErrors('invoice_adjustment_amount');
        $data = $this->payload();
        unset($data['invoice_adjustment_description']);
        $this->post('/lumber-invoices', $data)->assertSessionHasErrors('invoice_adjustment_description');
        $this->assertSame(0, LumberInvoice::count());
    }

    public function test_old_adjustments_remain_and_failed_edit_does_not_delete_data(): void
    {
        $data = $this->payload(0, 0);
        unset($data['lines'][0]['unit_price_adjustment'],$data['invoice_adjustment_amount'],$data['invoice_adjustment_description']);
        $data['lines'][0]['adjustment_amount'] = -100;
        $this->post('/lumber-invoices', $data)->assertSessionHasNoErrors();
        $invoice = LumberInvoice::firstOrFail();
        $this->assertEquals(2090, $invoice->total);
        $lineId = $invoice->lines->first()->id;
        $this->put('/lumber-invoices/'.$invoice->id, $this->payload(-105))->assertSessionHasErrors('lines');
        $this->assertEquals($lineId, $invoice->fresh()->lines->first()->id);
        $this->assertEquals(2090,$invoice->fresh()->total);
    }
}
