<p class="text-sm mb-4" style="margin-bottom:12px;">未払金の支払い分と未払計上分を分けて集計しています。各合計は対象年の伝票金額で、年初・年末の残高ではありません。</p>
@foreach($ledger['payable_sections'] as $section)
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
