<?php

namespace Database\Seeders;

use App\Models\WithholdingTaxBracket;
use Illuminate\Database\Seeder;

class WithholdingTax2025Seeder extends Seeder
{
    public function run(): void
    {
        // 国税庁 令和7年月額表・甲欄（復興特別所得税込み）。
        // https://www.nta.go.jp/publication/pamph/gensen/zeigakuhyo2024/data/01-07.pdf
        foreach (file(database_path('data/withholding_2025.csv'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $values = array_map('intval', str_getcsv($line, ',', '"', ''));
            $taxes = [];
            for ($i = 0; $i <= 7; $i++) {
                $taxes['dep_'.$i] = $values[$i + 2];
            }
            WithholdingTaxBracket::updateOrCreate(['tax_year' => 2025, 'min_amount' => $values[0], 'max_amount' => $values[1]], $taxes);
        }
    }
}
