<h3 class="font-bold mb-2" style="font-size:12px;">科目別合計</h3>
<table class="ledger w-full text-sm" style="margin-bottom:18px;">
<thead class="bg-gray-100"><tr><th class="p-2">科目</th><th class="p-2">前期繰越</th><th class="p-2">預り分合計（貸方）</th><th class="p-2">納付・精算分合計（借方）</th><th class="p-2">期末残高</th></tr></thead>
<tbody>@foreach($ledger['deposit_ledgers'] as $deposit)
<tr class="border-t"><td class="p-2">{{ $deposit['category']->name }}</td><td class="p-2 num text-right">¥{{ number_format(-$deposit['opening']) }}</td><td class="p-2 num text-right">¥{{ number_format(collect($deposit['rows'])->where('side','credit')->sum('amount')) }}</td><td class="p-2 num text-right">¥{{ number_format(collect($deposit['rows'])->where('side','debit')->sum('amount')) }}</td><td class="p-2 num text-right">¥{{ number_format(-$deposit['closing']) }}</td></tr>
@endforeach</tbody></table>
<p class="text-sm mb-4" style="margin-bottom:12px;">残高は貸方残高で表示しています。預り分と納付・精算分は相殺せず、それぞれ合計しています。</p>
@foreach($ledger['deposit_ledgers'] as $deposit)
<h3 class="font-bold mb-2" style="font-size:12px; margin-top:16px;">{{ $deposit['category']->name }} 明細</h3>
<table class="ledger w-full text-sm" style="margin-bottom:18px;">
<thead class="bg-gray-100"><tr><th class="p-2">日付</th><th class="p-2">項目</th><th class="p-2">相手先</th><th class="p-2">預り（貸方）</th><th class="p-2">納付・精算（借方）</th><th class="p-2">残高</th></tr></thead>
<tbody>@forelse($deposit['rows'] as $row)
<tr class="border-t"><td class="p-2">{{ $row['date']->format('n/j') }} @include('general_ledger.edit_link')</td><td class="p-2">{{ $row['item'] }}</td><td class="p-2">{{ $row['payee'] }}</td><td class="p-2 num text-right">{{ $row['side']==='credit' ? '¥'.number_format($row['amount']) : '-' }}</td><td class="p-2 num text-right">{{ $row['side']==='debit' ? '¥'.number_format($row['amount']) : '-' }}</td><td class="p-2 num text-right">¥{{ number_format(-$row['balance']) }}</td></tr>
@empty<tr><td class="p-2" colspan="6">該当する明細はありません。</td></tr>@endforelse</tbody></table>
@endforeach
