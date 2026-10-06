@extends('layouts.app')
@section('title', $type.'出納帳')
@section('content')
<h1 class="text-xl font-bold mb-6">{{ $type }}出納帳</h1>

<div class="bg-white p-6 rounded shadow mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">種別</label>
            <select name="type" class="border rounded p-2" onchange="this.form.submit()">
                <option value="現金" @selected($type === '現金')>現金出納帳</option>
                <option value="銀行預金" @selected($type === '銀行預金')>預金出納帳</option>
            </select>
        </div>

        @if ($type === '現金')
        <div>
            <label class="block text-xs text-gray-500 mb-1">対象月</label>
            <input type="month" name="month" value="{{ $month }}" class="border rounded p-2" onchange="this.form.submit()">
        </div>
        <a href="{{ route('cash-book.pdf', ['type' => $type, 'month' => $month]) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded text-sm">PDF出力(月分)</a>
        @else
        <div>
            <label class="block text-xs text-gray-500 mb-1">年</label>
            <select name="year" class="border rounded p-2" onchange="this.form.submit()">
                @for ($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}年</option>
                @endfor
            </select>
        </div>
        <a href="{{ route('cash-book.pdf', ['type' => $type, 'year' => $year]) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded text-sm">PDF出力(年分)</a>
        @endif
    </form>
</div>

@if ($book['category'])
<div class="bg-white p-6 rounded shadow">
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">日付</th>
                <th class="p-2">摘要</th>
                <th class="p-2">支払い先</th>
                <th class="p-2 text-right">収入</th>
                <th class="p-2 text-right">支出</th>
                <th class="p-2 text-right">残高</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-t bg-gray-50 font-bold">
                <td class="p-2" colspan="5">前期繰越</td>
                <td class="p-2 text-right">¥{{ number_format($book['opening']) }}</td>
            </tr>
            @foreach ($book['rows'] as $row)
            <tr class="border-t">
                <td class="p-2">{{ $row['date']->format('n/j') }} @include('general_ledger.edit_link', ['showEdit' => true])</td>
                <td class="p-2">{{ $row['description'] }}</td>
                <td class="p-2">{{ $row['payee'] }}</td>
                <td class="p-2 text-right">{{ $row['income'] ? '¥'.number_format($row['income']) : '' }}</td>
                <td class="p-2 text-right">{{ $row['expense'] ? '¥'.number_format($row['expense']) : '' }}</td>
                <td class="p-2 text-right">¥{{ number_format($row['balance']) }}</td>
            </tr>
            @endforeach
            <tr class="border-t bg-gray-50 font-bold">
                <td class="p-2" colspan="3">期間合計・繰越</td>
                <td class="p-2 text-right">¥{{ number_format($book['income_total']) }}</td>
                <td class="p-2 text-right">¥{{ number_format($book['expense_total']) }}</td>
                <td class="p-2 text-right"><span class="block text-xs">{{ $type === '現金' ? '次月繰越' : '翌年繰越' }}</span>¥{{ number_format($book['closing']) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="mt-4 border rounded p-4 bg-gray-50 text-sm">
        <h2 class="font-bold mb-3">繰越を含む収支の照合</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><div>前期繰越＋収入合計</div><div class="text-gray-600">¥{{ number_format($book['opening']) }} ＋ ¥{{ number_format($book['income_total']) }}</div><div class="font-bold">¥{{ number_format($book['receipt_total']) }}</div></div>
            <div><div>{{ $type === '現金' ? '次月繰越' : '翌年繰越' }}＋支出合計</div><div class="text-gray-600">¥{{ number_format($book['closing']) }} ＋ ¥{{ number_format($book['expense_total']) }}</div><div class="font-bold">¥{{ number_format($book['payment_total']) }}</div></div>
        </div>
        <p class="mt-3 font-bold {{ $book['balanced'] ? 'text-green-700' : 'text-red-700' }}">{{ $book['balanced'] ? '一致しています' : '一致していません' }}（差額：¥{{ number_format($book['receipt_total'] - $book['payment_total']) }}）</p>
        <p class="mt-1 text-xs text-gray-500">帳簿内の計算の一致を確認しています。実際の現金・銀行残高との照合は別途必要です。</p>
    </div>
</div>
@else
<p class="text-gray-500">「{{ $type }}」という勘定科目が見つかりませんでした。</p>
@endif
@endsection