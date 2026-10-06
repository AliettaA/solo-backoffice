<?php

namespace App\Http\Controllers;

use App\Models\LumberSpecies;
use App\Models\LumberWidthPrice;
use App\Models\PricingSetting;
use App\Models\QuantityTierSurcharge;
use App\Models\ThicknessSurcharge;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PricingMasterController extends Controller
{
    public function index()
    {
        return view('pricing_master.index', [
            'speciesList' => LumberSpecies::with('widthPrices')->orderBy('id')->get(),
            'thicknessSurcharges' => ThicknessSurcharge::orderBy('min_thickness')->get(),
            'quantityTiers' => QuantityTierSurcharge::orderBy('sort_order')->get(),
            'settings' => PricingSetting::pluck('value', 'key'),
        ]);
    }

    // ---------- 樹種 ----------

    public function storeSpecies(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('lumber_species', 'name')],
            'pricing_type' => ['required', 'in:by_width,flat'],
            'flat_price' => ['required_if:pricing_type,flat', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'apply_thickness_surcharge' => ['nullable', 'boolean'],
            'apply_length_adjustment' => ['nullable', 'boolean'],
        ]);

        $data['apply_thickness_surcharge'] = $request->boolean('apply_thickness_surcharge');
        $data['apply_length_adjustment'] = $request->boolean('apply_length_adjustment');

        LumberSpecies::create($data);

        return back()->with('status', '樹種を追加しました');
    }

    public function editSpecies(LumberSpecies $species)
    {
        $species->load('widthPrices');

        return view('pricing_master.species_edit', compact('species'));
    }

    public function updateSpecies(Request $request, LumberSpecies $species)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('lumber_species', 'name')->ignore($species->id)],
            'pricing_type' => ['required', 'in:by_width,flat'],
            'flat_price' => ['required_if:pricing_type,flat', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'apply_thickness_surcharge' => ['nullable', 'boolean'],
            'apply_length_adjustment' => ['nullable', 'boolean'],
        ]);

        $data['apply_thickness_surcharge'] = $request->boolean('apply_thickness_surcharge');
        $data['apply_length_adjustment'] = $request->boolean('apply_length_adjustment');

        $species->update($data);

        return redirect()->route('pricing-master.species.edit', $species)->with('status', '樹種を更新しました');
    }

    public function destroySpecies(LumberSpecies $species)
    {
        if (\App\Models\LumberInvoiceLine::where('lumber_species_id', $species->id)->exists()) {
            throw ValidationException::withMessages(['name' => '納品書で使用中の樹種は削除できません。']);
        }
        $species->delete();

        return redirect()->route('pricing-master.index')->with('status', '樹種を削除しました');
    }

    // ---------- 幅ごとの基本単価 ----------

    public function storeWidthPrice(Request $request, LumberSpecies $species)
    {
        $data = $request->validate([
            'width' => ['required', 'numeric', 'min:0'],
            'base_price' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);

        $species->widthPrices()->updateOrCreate(
            ['width' => $data['width']],
            ['base_price' => $data['base_price']]
        );

        return back()->with('status', '基本単価を登録しました');
    }

    public function destroyWidthPrice(LumberWidthPrice $widthPrice)
    {
        $species = $widthPrice->lumber_species_id;
        $widthPrice->delete();

        return back()->with('status', '基本単価を削除しました');
    }

    // ---------- 厚み加算 ----------

    public function storeThicknessSurcharge(Request $request)
    {
        $data = $request->validate([
            'min_thickness' => ['required', 'integer', 'min:0'],
            'surcharge_amount' => ['required', 'integer'],
        ]);

        ThicknessSurcharge::updateOrCreate(
            ['min_thickness' => $data['min_thickness']],
            ['surcharge_amount' => $data['surcharge_amount']]
        );

        return back()->with('status', '厚み加算を登録しました');
    }

    public function destroyThicknessSurcharge(ThicknessSurcharge $thicknessSurcharge)
    {
        $thicknessSurcharge->delete();

        return back()->with('status', '厚み加算を削除しました');
    }

    // ---------- 数量帯加算 ----------

    public function storeQuantityTier(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:20', Rule::unique('quantity_tier_surcharges', 'label')],
            'min_qty' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'max_qty' => ['nullable', 'integer', 'gte:min_qty', 'max:4294967295'],
            'surcharge_amount' => ['required', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        QuantityTierSurcharge::create($data);

        return back()->with('status', '数量帯を追加しました');
    }

    public function updateQuantityTier(Request $request, QuantityTierSurcharge $quantityTier)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:20', Rule::unique('quantity_tier_surcharges', 'label')->ignore($quantityTier->id)],
            'min_qty' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'max_qty' => ['nullable', 'integer', 'gte:min_qty', 'max:4294967295'],
            'surcharge_amount' => ['required', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $quantityTier->update($data);

        return back()->with('status', '数量帯を更新しました');
    }

    public function destroyQuantityTier(QuantityTierSurcharge $quantityTier)
    {
        $quantityTier->delete();

        return back()->with('status', '数量帯を削除しました');
    }

    // ---------- 設定値(長さ調整など) ----------

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'base_length_m' => ['required', 'numeric', 'gt:0'],
            'base_price_min_length_m' => ['required', 'numeric', 'gt:0', 'lte:base_length_m'],
            'price_per_meter_diff' => ['required', 'numeric'],
        ]);

        foreach ($data as $key => $value) {
            PricingSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('status', '設定を更新しました');
    }
}