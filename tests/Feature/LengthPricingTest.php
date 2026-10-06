<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Services\LumberPriceCalculator;
use App\Models\PricingSetting;
class LengthPricingTest extends TestCase
{
    public function test_length_exemption_preserves_other_adjustments(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_03_231058_create_pricing_settings_table.php'))->up();
        $calculator = new LumberPriceCalculator;
        foreach ([[4,0],[3.8,0],[3.7,0],[3.6,-4],[3,-10],[2,-20],[5,10]] as [$length,$amount]) {
            $this->assertSame($amount,$calculator->lengthAdjustment($length));
        }
        PricingSetting::updateOrCreate(['key'=>'base_price_min_length_m'],['value'=>3.8]);
        $this->assertSame(-3,$calculator->lengthAdjustment(3.7));
        $this->assertSame(0,$calculator->lengthAdjustment(3.8));
    }
}
