@extends('layouts.app')
@section('title', '貸借対照表・損益計算書')
@section('content')
<style>@media print {nav,.no-print{display:none!important}body{background:white!important}main{max-width:none!important;padding:0!important}.statement{break-inside:avoid;box-shadow:none!important}details{display:none}th,td{font-size:11pt}}</style>
<div class="flex flex-wrap justify-between gap-3 mb-4"><h1 class="text-xl font-bold">{{ $year }}年分 決算書（一般用・集計表）</h1><form class="no-print flex gap-2" method="GET"><input type="number" name="year" min="1900" max="2100" value="{{ $year }}" class="border rounded p-2 w-28"><button class="bg-blue-600 text-white rounded px-3">表示</button><a href="{{ route('financial-statements.pdf', ['year' => $year]) }}" target="_blank" rel="noopener" class="border rounded px-3 py-2">決算書PDF（2ページ）</a></form></div>
<p class="text-sm mb-4">集計期間：{{ $year }}年1月1日〜12月31日。振替伝票と期首残高を集計しています。未入力の取引・棚卸・減価償却・決算整理は振替伝票に登録してください。青色申告特別控除前の金額です。</p>
@if(count($warnings))<div class="bg-amber-50 border border-amber-300 p-4 mb-4"><strong>確認事項（入力途中の集計です）</strong><ul class="list-disc pl-5 text-sm mt-2">@foreach($warnings as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
<section class="statement bg-white shadow rounded p-5 mb-6">
<h2 class="font-bold text-lg mb-3">損益計算書</h2>
<table class="w-full text-sm"><thead><tr class="bg-gray-100"><th class="text-left p-2">科目</th><th class="text-right p-2">金額（円）</th></tr></thead><tbody>
@foreach(['revenue'=>'売上・雑収入','inventory_start'=>'期首棚卸高','purchase'=>'仕入','inventory_end'=>'期末棚卸高（控除）','expense'=>'経費','family_salary'=>'専従者給与'] as $type=>$label)
<tr class="bg-gray-50"><th class="p-2 text-left" colspan="2">{{ $label }}</th></tr>
@foreach($rows->where('type', $type) as $row)
<tr class="border-t"><td class="p-2">{{ $row['name'] }}</td><td class="p-2 text-right">{{ number_format($row['movement'] * (in_array($type, ['revenue','inventory_end']) ? -1 : 1)) }}</td></tr>
@endforeach
@endforeach
@foreach(['売上・雑収入 合計'=>$revenue,'売上原価（期首棚卸＋仕入－期末棚卸）'=>$cost,'差引売上総利益'=>$revenue-$cost,'経費 合計'=>$expenses,'専従者給与 合計'=>$family,'青色申告特別控除前の所得金額'=>$profit] as $label=>$amount)
<tr class="border-t font-bold"><td class="p-2">{{ $label }}</td><td class="p-2 text-right">{{ number_format($amount) }}</td></tr>
@endforeach
</tbody></table>
</section>
<section class="statement bg-white shadow rounded p-5 mb-6">
<h2 class="font-bold text-lg mb-3">貸借対照表</h2>
<table class="w-full text-sm"><thead><tr class="bg-gray-100"><th class="text-left p-2">科目</th><th class="text-right p-2">期首 1月1日（円）</th><th class="text-right p-2">期末 12月31日（円）</th></tr></thead><tbody>
@foreach(['asset'=>'資産の部','owner_debit'=>'事業主貸','liability'=>'負債の部','owner_credit'=>'事業主借'] as $type=>$label)
<tr class="bg-gray-50"><th class="p-2 text-left" colspan="3">{{ $label }}</th></tr>
@foreach($rows->where('type', $type) as $row)
@php($sign = in_array($type, ['liability','owner_credit']) ? -1 : 1)
@php($owner = in_array($type, ['owner_debit','owner_credit']))
<tr class="border-t"><td class="p-2">{{ $row['name'] }}</td><td class="p-2 text-right">{{ number_format($owner ? 0 : $row['opening']*$sign) }}</td><td class="p-2 text-right">{{ number_format(($owner ? $row['movement'] : $row['closing'])*$sign) }}</td></tr>
@endforeach
@endforeach
<tr class="border-t"><td class="p-2">元入金{{ $capitalExplicit ? '' : '（期首資産－期首負債）' }}</td><td class="p-2 text-right">{{ number_format($capital) }}</td><td class="p-2 text-right">{{ number_format($capital-$rows->where('type','capital')->sum('movement')) }}</td></tr>
<tr class="border-t"><td class="p-2">青色申告特別控除前の所得金額</td><td class="p-2 text-right">—</td><td class="p-2 text-right">{{ number_format($profit) }}</td></tr>
<tr class="border-t font-bold"><td class="p-2">資産の部 合計（事業主貸を含む）</td><td class="p-2 text-right">{{ number_format($openingAssets) }}</td><td class="p-2 text-right">{{ number_format($assetTotal) }}</td></tr>
<tr class="border-t font-bold"><td class="p-2">負債・資本の部 合計</td><td class="p-2 text-right">{{ number_format($openingLiabilities+$capital) }}</td><td class="p-2 text-right">{{ number_format($liabilityTotal) }}</td></tr>
<tr class="border-t font-bold {{ $difference || $openingDifference ? 'text-red-700' : 'text-green-700' }}"><td class="p-2">貸借差額（0円で一致）</td><td class="p-2 text-right">{{ number_format($openingDifference) }}</td><td class="p-2 text-right">{{ number_format($difference) }}</td></tr>
</tbody></table>
<p class="text-sm mt-3">差額が0円でも、入力漏れや科目の誤りがないことを保証するものではありません。前年の決算書・実際の残高と照合してください。</p>
</section>
<details class="no-print bg-white border rounded p-4" @if($rows->contains(fn($r)=>!$r['type'])) open @endif><summary class="font-bold cursor-pointer">科目分類・期首残高の設定</summary>
<p class="my-3 text-sm">未分類の科目は決算書の合計に含まれません。預金利息などの取扱いも確認し、適切な区分を選んでください。</p>
<a class="text-blue-600 underline" href="{{ route('opening-balances.index') }}">期首残高を登録・確認</a>
@foreach($categories as $category)
@php($type = $category->statement_type ?: \App\Services\FinancialStatements::defaultType($category->name))
<form method="POST" action="{{ route('financial-statements.classify',$category) }}" class="flex gap-3 items-center border-t py-2 mt-2">@csrf @method('PUT')<span class="w-48">{{ $category->name }}</span><select name="statement_type" class="border rounded p-2"><option value="">未分類／名前から自動判定</option>@foreach(\App\Services\FinancialStatements::TYPES as $value=>$label)<option value="{{ $value }}" @selected($type===$value)>{{ $label }}</option>@endforeach</select><button class="border rounded px-3 py-2">保存</button></form>
@endforeach
<form method="POST" action="{{ route('expense-categories.store') }}" class="border-t pt-3 mt-3 flex gap-2">@csrf<label>科目の追加<input name="name" required maxlength="50" class="border rounded p-2 ml-2" placeholder="例：売掛金、元入金"></label><button class="border rounded px-3">追加</button></form>
</details>
<p class="text-sm mt-4">申告書への転記を確認するための集計表です。<a class="text-blue-600 underline" href="https://www.nta.go.jp/taxes/shiraberu/shinkoku/syotoku/r07.htm" target="_blank" rel="noopener">国税庁の令和7年分様式・手引き</a></p>
@endsection
