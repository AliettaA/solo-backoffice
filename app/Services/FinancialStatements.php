<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use App\Models\JournalEntryLine;
use App\Models\OpeningBalance;
use App\Models\SalaryPayment;

class FinancialStatements
{
    public const TYPES = [
        'asset' => '資産', 'liability' => '負債', 'capital' => '元入金',
        'owner_debit' => '事業主貸', 'owner_credit' => '事業主借',
        'revenue' => '売上・雑収入', 'purchase' => '仕入', 'expense' => '経費',
        'family_salary' => '専従者給与', 'inventory_start' => '期首棚卸高', 'inventory_end' => '期末棚卸高',
    ];

    public static function defaultType(string $name): ?string
    {
        $groups = [
            'asset' => ['現金', '銀行預金', '普通預金', '当座預金', '定期預金', '売掛金', '受取手形', '棚卸資産', '商品', '製品', '原材料', '建物', '建物附属設備', '機械装置', '車両運搬具', '工具器具備品', '土地', '前払金', '前払費用', '未収入金', '貸付金', '事業用資産'],
            'liability' => ['未払金', '買掛金', '支払手形', '借入金', '短期借入金', '長期借入金', '預り金', '源泉所得税預り金', '市県民税預り金', '未払費用', '前受金', '未払消費税'],
            'capital' => ['元入金'], 'owner_debit' => ['事業主貸'], 'owner_credit' => ['事業主借'],
            'revenue' => ['売上', '売上高', '雑収入'], 'purchase' => ['仕入', '仕入高'],
            'expense' => ['租税公課', '水道光熱費', '旅費交通費', '通信費', '接待交際費', '損害保険料', '修繕費', '消耗品費', '消耗工具費', '減価償却費', '福利厚生費', '給料賃金', '給料', '地代家賃', '雑費', '労務費', '目立費', '事務用品費', '外注工賃', '利子割引料', '貸倒金', '荷造運賃', '広告宣伝費', '支払手数料'],
            'family_salary' => ['専従者給与', '専従者給与額'],
            'inventory_start' => ['期首商品棚卸高', '期首棚卸高'], 'inventory_end' => ['期末商品棚卸高', '期末棚卸高'],
        ];
        foreach ($groups as $type => $names) {
            if (in_array($name, $names, true)) {
                return $type;
            }
        }

        return null;
    }

    public function build(int $year): array
    {
        $start = "$year-01-01";
        $end = "$year-12-31";
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('id')->get();
        $lines = JournalEntryLine::with('entry')->whereHas('entry', fn ($q) => $q->whereDate('entry_date', '<=', $end))->get()->groupBy('expense_category_id');
        $balances = OpeningBalance::whereDate('effective_date', '<=', $start)->orderByDesc('effective_date')->get()->unique('expense_category_id')->keyBy('expense_category_id');
        $warnings = [];
        $rows = [];
        // Older automatic salary vouchers use 給料賃金 for both employee types.
        $familyPayments = SalaryPayment::with('employee')->whereHas('employee', fn ($q) => $q->where('employment_type', 'family'))->get()->keyBy(fn ($p) => '給与:salary_payment_'.$p->id);
        $familyAmount = 0;
        foreach ($categories as $category) {
            $type = $category->statement_type ?: self::defaultType($category->name);
            $openingRecord = $balances->get($category->id);
            $opening = (int) ($openingRecord?->amount ?? 0);
            $movement = 0;
            $active = $opening !== 0;
            foreach ($lines->get($category->id, collect()) as $line) {
                $date = $line->entry->entry_date->toDateString();
                $signed = (int) $line->amount * ($line->side === 'debit' ? 1 : -1);
                if ($date < $start) {
                    if (! $openingRecord || $date >= $openingRecord->effective_date->toDateString()) {
                        $opening += $signed;
                    }

                    continue;
                }
                $active = true;
                $payment = $familyPayments->get($line->entry->description);
                if ($type === 'expense' && in_array($category->name, ['給料賃金', '給料'], true)
                    && $line->side === 'debit' && $payment
                    && (int) $line->amount === (int) ($payment->gross_amount ?: $payment->amount)) {
                    $familyAmount += $signed;
                } else {
                    $movement += $signed;
                }
            }
            $active = $active || $opening !== 0;
            if (! $type && $active) {
                $warnings[] = '「'.$category->name.'」が未分類です。科目分類を設定してください。';
            }
            if ($openingRecord && $openingRecord->effective_date->year < $year && in_array($type, ['asset', 'liability', 'capital'], true)) {
                $warnings[] = '「'.$category->name.'」の期首残高は'.$openingRecord->effective_date->format('Y/m/d').'からの仕訳を加減して計算しています。';
            }
            $rows[] = ['id' => $category->id, 'name' => $category->name, 'type' => $type, 'opening' => $opening, 'movement' => $movement, 'closing' => $opening + $movement, 'has_opening' => $openingRecord && $openingRecord->effective_date->toDateString() === $start];
        }
        if ($familyAmount !== 0) {
            $rows[] = ['id' => null, 'name' => '専従者給与（給与支払いから集計）', 'type' => 'family_salary', 'opening' => 0, 'movement' => $familyAmount, 'closing' => $familyAmount, 'has_opening' => false];
            $warnings[] = '給与支払いに紐づく専従者の給与は、現在の従業員区分を使い給料・給料賃金から専従者給与に振り分けています。';
        }
        $rows = collect($rows);
        $sum = fn ($type, $field) => (int) $rows->where('type', $type)->sum($field);
        $revenue = -$sum('revenue', 'movement');
        $cost = $sum('purchase', 'movement') + $sum('inventory_start', 'movement') + $sum('inventory_end', 'movement');
        $expenses = $sum('expense', 'movement');
        $family = $sum('family_salary', 'movement');
        $profit = $revenue - $cost - $expenses - $family;
        $openingAssets = $sum('asset', 'opening');
        $openingLiabilities = -$sum('liability', 'opening');
        $capitalRows = $rows->where('type', 'capital');
        $capitalExplicit = $capitalRows->contains(fn ($r) => $r['has_opening']);
        $capital = $capitalExplicit ? -$sum('capital', 'opening') : $openingAssets - $openingLiabilities;
        if (! $capitalExplicit) {
            $warnings[] = '元入金は期首資産－期首負債から算出しています。現金・預金だけでなく、売掛金・棚卸資産・固定資産・未払金・借入金などの期首残高も確認してください。';
        }
        $assetTotal = $sum('asset', 'closing') + $sum('owner_debit', 'movement');
        $liabilityTotal = -$sum('liability', 'closing') - $sum('owner_credit', 'movement') + $capital - $sum('capital', 'movement') + $profit;
        $difference = $assetTotal - $liabilityTotal;
        $openingDifference = $openingAssets - $openingLiabilities - $capital;
        if (OpeningBalance::whereBetween('effective_date', [$start, $end])->whereDate('effective_date', '>', $start)->exists()) {
            $warnings[] = '年の途中を基準日とする残高があります。この決算書では1月1日以前の残高を使います。出納帳との基準日の違いを確認してください。';
        }

        return compact('year', 'categories', 'rows', 'warnings', 'revenue', 'cost', 'expenses', 'family', 'profit', 'capital', 'capitalExplicit', 'openingAssets', 'openingLiabilities', 'openingDifference', 'assetTotal', 'liabilityTotal', 'difference');
    }
}
