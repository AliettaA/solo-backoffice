<p style="margin-bottom:12px;">源泉所得税の預り分を相手科目で分類しています。納付・精算分は内訳を推定せず、別に表示します。</p>
@foreach($ledger['withholding_sections'] as $section)
<h3 class="font-bold mb-2" style="font-size:12px; margin-top:16px; margin-bottom:6px;">{{ $section['title'] }}</h3>
<table class="ledger w-full text-sm" style="margin-bottom:16px;">
    <thead class="bg-gray-100 text-left"><tr>
        <th class="p-2" style="width:55px;">日付</th><th class="p-2">項目</th><th class="p-2">支払い先</th><th class="p-2 text-right" style="width:90px;">金額</th>
    </tr></thead>
    <tbody>
        @forelse($section['rows'] as $row)
        <tr class="border-t"><td class="p-2 center">{{ $row['date']->format('n/j') }} @include('general_ledger.edit_link')</td><td class="p-2">{{ $row['item'] }}</td><td class="p-2">{{ $row['payee'] }}</td><td class="p-2 num text-right">¥{{ number_format($row['amount']) }}</td></tr>
        @empty
        <tr><td colspan="4" class="p-2">該当する明細はありません。</td></tr>
        @endforelse
        <tr class="closing-row border-t bg-gray-50 font-bold"><td class="p-2" colspan="3">{{ $section['title'] }} 合計（{{ count($section['rows']) }}件）</td><td class="p-2 num text-right">¥{{ number_format($section['total']) }}</td></tr>
    </tbody>
</table>
@endforeach
<table class="ledger w-full text-sm">
<tbody>
<tr class="opening-row"><td class="p-2">前期繰越（貸方残高）</td><td class="p-2 num text-right">¥{{ number_format(-$ledger['opening']) }}</td></tr>
<tr class="closing-row"><td class="p-2">預り分合計（貸方）</td><td class="p-2 num text-right">¥{{ number_format(collect($ledger['rows'])->where('side', 'credit')->sum('amount')) }}</td></tr>
<tr class="closing-row"><td class="p-2">納付・精算分合計（借方）</td><td class="p-2 num text-right">¥{{ number_format(collect($ledger['rows'])->where('side', 'debit')->sum('amount')) }}</td></tr>
<tr class="closing-row"><td class="p-2">期末残高（貸方残高）</td><td class="p-2 num text-right">¥{{ number_format(-$ledger['closing']) }}</td></tr>
</tbody></table>
