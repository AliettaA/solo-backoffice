<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            '租税公課',
            '水道光熱費',
            '旅費交通費',
            '通信費',
            '未払金',
            '接待交際費',
            '損害保険料',
            '修繕費',
            '消耗品費',
            '消耗工具費',
            '減価償却費',
            '福利厚生費',
            '給料賃金',
            '現金',
            '銀行預金',
            '地代家賃',
            '雑費',
            '仕入',
            '労務費',
            '給料',
            '目立費',
            '事務用品費',
            '機械装置',
            '事業主借',
            '預り金',
            '源泉所得税預り金',
            '市県民税預り金', '売上', '売掛金', '受取利息', '元入金', '事業主貸',
        ];

        foreach ($categories as $i => $name) {
            ExpenseCategory::firstOrCreate(['name' => $name], ['sort_order' => $i]);
        }
    }
}