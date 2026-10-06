<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Employee;
use App\Models\LumberInvoice;
use App\Models\LumberSpecies;
use App\Models\Product;
use App\Models\QuantityTierSurcharge;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        foreach (['*create_clients*', '*create_products*', '*create_lumber_species*', '*create_lumber_width_prices*', '*create_thickness_surcharges*', '*create_quantity_tier_surcharges*', '*create_pricing_settings*', '*create_lumber_invoices*', '*create_lumber_invoice_lines*', '*create_lumber_invoice_line_tiers*', '*create_lumber_invoice_product_lines*', '*add_mixed_tax_columns*', '*add_invoice_adjustments*', '*add_returned_quantity*', '*create_client_payments*', '*create_expense_categories*', '*create_journal_entries*', '*create_journal_entry_lines*', '*create_estimates_table*', '*create_invoices_table*', '*create_employees*', '*create_attendances*', '*create_salary_payments*', '*add_dependents*', '*add_payroll_fields*', '*add_resident_tax*', '*add_withholding_tax*', '*create_payees*'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern.'.php')) as $file) {
                (require $file)->up();
            }
        }
    }

    private function invoiceData(): array
    {
        $client = Client::firstOrCreate(['name' => 'テスト取引先']);
        $species = LumberSpecies::firstOrCreate(['name' => '杉'], ['pricing_type' => 'flat', 'flat_price' => 100, 'apply_thickness_surcharge' => false, 'apply_length_adjustment' => false]);
        $tier = QuantityTierSurcharge::firstOrCreate(['label' => '20-'], ['min_qty' => 20, 'surcharge_amount' => 0]);
        return ['client_id' => $client->id, 'issue_date' => '2025-01-10', 'lines' => [
            ['lumber_species_id' => $species->id, 'thickness' => 18, 'width' => 12, 'length' => 4, 'quantities' => [$tier->id => 20]],
        ]];
    }

    public function test_invalid_invoice_rows_are_not_silently_discarded(): void
    {
        $data = $this->invoiceData();
        $id = array_key_first($data['lines'][0]['quantities']);
        foreach ([-1, 0.5, 'abc'] as $quantity) {
            $bad = $data;
            $bad['lines'][0]['quantities'][$id] = $quantity;
            $this->post('/lumber-invoices', $bad)->assertSessionHasErrors('lines.0.quantities.'.$id);
        }
        $bad = $data;
        $bad['lines'][0]['quantities'] = [99999 => 2];
        $this->post('/lumber-invoices', $bad)->assertSessionHasErrors('lines.0.quantities');
        $bad = $data;
        $bad['lines'][0]['length'] = 0;
        $this->post('/lumber-invoices', $bad)->assertSessionHasErrors('lines.0.length');
        $bad = $data;
        $bad['lines'] = [];
        $this->post('/lumber-invoices', $bad)->assertSessionHasErrors('lines');
        $bad['product_lines'] = [['quantity' => 2, 'unit_price' => 100]];
        $this->post('/lumber-invoices', $bad)->assertSessionHasErrors('product_lines.0.description');
        $bad['product_lines'][0]['description'] = '品目';
        unset($bad['product_lines'][0]['unit_price']);
        $this->post('/lumber-invoices', $bad)->assertSessionHasErrors('product_lines.0.unit_price');
        $this->assertSame(0, LumberInvoice::count());
        $this->post('/lumber-invoices', $data)->assertSessionHasNoErrors();
        $invoice = LumberInvoice::firstOrFail();
        $this->put('/lumber-invoices/'.$invoice->id, $bad)->assertSessionHasErrors('product_lines.0.unit_price');
        $this->assertEquals(2200, $invoice->fresh()->total);
        $this->assertSame(1, $invoice->fresh()->lines()->count());
    }

    public function test_duplicate_receipt_requires_confirmation_and_keeps_valid_same_day_receipts(): void
    {
        $client = Client::create(['name' => '入金先']);
        $data = ['client_id' => $client->id, 'paid_at' => '2025-01-31', 'target_month' => '2025-01', 'amount' => 10000, 'method' => '現金'];
        $this->post('/payments', $data)->assertSessionHasNoErrors();
        $this->from('/payments?month=2025-01')->post('/payments', $data)->assertSessionHasErrors('duplicate')->assertSessionHasInput('amount', 10000);
        $this->assertSame(1, ClientPayment::count());
        $this->assertSame(1, DB::table('journal_entries')->count());
        $this->get('/payments?month=2025-01')->assertOk()->assertSee('別の入金であることを確認しました');
        $this->post('/payments', $data + ['confirm_duplicate' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, ClientPayment::count());
        // Confirmation cannot be reused to create a third duplicate silently.
        $this->post('/payments', $data + ['confirm_duplicate' => 1])->assertSessionHasErrors('duplicate');
        $data['amount'] = 20000;
        $this->post('/payments', $data)->assertSessionHasNoErrors();
        $this->assertSame(3, ClientPayment::count());
        foreach (['paid_at' => '2025-02-30', 'method' => '', 'amount' => 0] as $field => $value) {
            $this->post('/payments', array_replace($data, [$field => $value]))->assertSessionHasErrors($field);
        }
        $this->delete('/clients/'.$client->id)->assertSessionHasErrors('name');
        $this->assertNotNull($client->fresh());
        $this->assertSame(3, ClientPayment::count());
    }

    public function test_used_masters_cannot_be_deleted_and_duplicates_are_rejected(): void
    {
        $data = $this->invoiceData();
        $this->post('/lumber-invoices', $data)->assertSessionHasNoErrors();
        $this->delete('/clients/'.$data['client_id'])->assertSessionHasErrors('name');
        $this->delete('/pricing-master/species/'.$data['lines'][0]['lumber_species_id'])->assertSessionHasErrors('name');
        $this->post('/clients', ['name' => 'テスト取引先'])->assertSessionHasErrors('name');
        $this->put('/clients/'.$data['client_id'], ['name' => 'テスト取引先'])->assertSessionHasNoErrors();
        $employee = Employee::create(['name' => '従業員', 'hourly_wage' => 1000]);
        $employee->attendances()->create(['work_date' => '2025-01-01', 'start_time' => '08:00', 'end_time' => '17:00']);
        $this->delete('/employees/'.$employee->id)->assertSessionHasErrors('name');
        $this->post('/employees', ['name' => '従業員', 'hourly_wage' => 1000, 'employment_type' => 'employee', 'dependents' => 0])->assertSessionHasErrors('name');
        $unused = Client::create(['name' => '未使用']);
        $this->delete('/clients/'.$unused->id)->assertSessionHasNoErrors();
        $this->assertNull($unused->fresh());
        $product = Product::create(['name' => '商品', 'unit_price' => 100, 'tax_rate' => 10, 'unit' => '式']);
        $invoice = LumberInvoice::firstOrFail();
        $invoice->productLines()->create(['product_id' => $product->id, 'description' => '商品', 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 10, 'amount' => 100]);
        $this->delete('/products/'.$product->id)->assertSessionHasErrors('name');
        $this->assertNotNull($product->fresh());
        $this->post('/pricing-master/species', ['name' => '杉', 'pricing_type' => 'flat', 'flat_price' => 100])->assertSessionHasErrors('name');
        $this->post('/pricing-master/quantity-tiers', ['label' => '不正範囲', 'min_qty' => 20, 'max_qty' => 10, 'surcharge_amount' => 0])->assertSessionHasErrors('max_qty');
    }
}
