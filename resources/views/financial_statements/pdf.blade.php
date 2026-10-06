<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><style>
@page { margin: 34px 36px; }
@font-face {font-family:ipaex;src:url('{{ resource_path('fonts/ipaexg.ttf') }}') format('truetype');}
@font-face {font-family:ipaex;src:url('{{ resource_path('fonts/ipaexg.ttf') }}') format('truetype');font-weight:bold;}
body {font-family:ipaex,sans-serif;font-size:11px;color:#222;line-height:1.3;}
h1 {font-size:22px;text-align:center;margin:0 0 8px;} .period{text-align:center;margin-bottom:20px;font-size:12px;}
.unit{text-align:right;margin-bottom:5px;} table{width:100%;border-collapse:collapse;table-layout:fixed;} th,td{border:1px solid #aaa;padding:5px 8px;overflow-wrap:break-word;} th{background:#edf0f2;} .num{text-align:right;white-space:nowrap;} .total{background:#f0f2f4;font-weight:bold;} .grand{background:#e3e8ec;font-weight:bold;} .note{font-size:10px;margin-top:12px;} .balance{page-break-before:always;} .balance td,.balance th{padding:8px 5px;font-size:10px;} .page{text-align:right;font-size:10px;margin-top:15px;}
</style></head><body>
@php
$active = $rows->filter(fn($r) => $r['movement'] != 0);
$assetRows = $rows->whereIn('type',['asset','owner_debit'])->map(fn($r)=>['name'=>$r['name'],'opening'=>$r['type']==='owner_debit'?0:$r['opening'],'closing'=>$r['type']==='owner_debit'?$r['movement']:$r['closing']])->filter(fn($r)=>$r['opening']!=0||$r['closing']!=0)->values();
$rightRows = $rows->whereIn('type',['liability','owner_credit'])->map(fn($r)=>['name'=>$r['name'],'opening'=>$r['type']==='owner_credit'?0:-$r['opening'],'closing'=>$r['type']==='owner_credit'?-$r['movement']:-$r['closing']])->filter(fn($r)=>$r['opening']!=0||$r['closing']!=0)->values();
$rightRows->push(['name'=>'元入金','opening'=>$capital,'closing'=>$capital-$rows->where('type','capital')->sum('movement')]);
$rightRows->push(['name'=>'青色申告特別控除前の所得金額','opening'=>null,'closing'=>$profit]);
$unclassified = $rows->filter(fn($r)=>!$r['type']&&($r['opening']||$r['movement']||$r['closing']));
@endphp
<h1>損益計算書</h1><div class="period">{{ $year }}年1月1日 ～ {{ $year }}年12月31日</div><div class="unit">単位：円</div>
<table><colgroup><col style="width:70%"><col style="width:30%"></colgroup><thead><tr><th>科目</th><th>金額</th></tr></thead><tbody>
@foreach($active->where('type','revenue') as $row)<tr><td>{{ $row['name'] }}</td><td class="num">{{ number_format(-$row['movement']) }}</td></tr>@endforeach
<tr class="total"><td>売上・雑収入 合計</td><td class="num">{{ number_format($revenue) }}</td></tr>
@foreach(['inventory_start','purchase','inventory_end'] as $type)
@foreach($active->where('type',$type) as $row)<tr><td>{{ $row['name'] }}{{ $type==='inventory_end'?'（控除）':'' }}</td><td class="num">{{ number_format($row['movement']*($type==='inventory_end'?-1:1)) }}</td></tr>@endforeach
@endforeach
<tr class="total"><td>売上原価</td><td class="num">{{ number_format($cost) }}</td></tr>
<tr class="total"><td>差引売上総利益</td><td class="num">{{ number_format($revenue-$cost) }}</td></tr>
@foreach($active->where('type','expense') as $row)<tr><td>{{ $row['name'] }}</td><td class="num">{{ number_format($row['movement']) }}</td></tr>@endforeach
<tr class="total"><td>経費 合計</td><td class="num">{{ number_format($expenses) }}</td></tr>
<tr><td>専従者給与</td><td class="num">{{ number_format($family) }}</td></tr>
<tr class="grand"><td>青色申告特別控除前の所得金額</td><td class="num">{{ number_format($profit) }}</td></tr>
</tbody></table>
@if($unclassified->isNotEmpty())<p class="note">未分類の科目があります（集計対象外）：{{ $unclassified->pluck('name')->implode('、') }}</p>@endif
<div class="page">1 / 2</div>
<div class="balance"><h1>貸借対照表</h1><div class="period">{{ $year }}年12月31日現在（期首・期末比較）</div><div class="unit">単位：円</div>
<table><colgroup><col style="width:22%"><col style="width:14%"><col style="width:14%"><col style="width:22%"><col style="width:14%"><col style="width:14%"></colgroup>
<thead><tr><th colspan="3">資産の部</th><th colspan="3">負債・資本の部</th></tr><tr><th>科目</th><th>期首 1/1</th><th>期末 12/31</th><th>科目</th><th>期首 1/1</th><th>期末 12/31</th></tr></thead><tbody>
@for($i=0;$i<max($assetRows->count(),$rightRows->count());$i++)
<tr>@foreach([$assetRows->get($i),$rightRows->get($i)] as $r)<td>{{ $r['name'] ?? '' }}</td><td class="num">{{ $r ? ($r['opening']===null?'—':number_format($r['opening'])) : '' }}</td><td class="num">{{ $r ? number_format($r['closing']) : '' }}</td>@endforeach</tr>
@endfor
<tr class="grand"><td>合計</td><td class="num">{{ number_format($openingAssets) }}</td><td class="num">{{ number_format($assetTotal) }}</td><td>合計</td><td class="num">{{ number_format($openingLiabilities+$capital) }}</td><td class="num">{{ number_format($liabilityTotal) }}</td></tr>
</tbody></table>
<p class="note">貸借差額（資産－負債・資本）：期首 {{ number_format($openingDifference) }}円 ／ 期末 {{ number_format($difference) }}円</p>
@if(!$capitalExplicit)<p class="note">元入金は期首資産から期首負債を差し引いて算出しています。</p>@endif
<div class="page">2 / 2</div></div></body></html>
