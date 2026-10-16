@extends('layouts.app')
@section('title', '納品書一覧')
@section('content')
<div class="flex flex-wrap gap-3 justify-between items-center mb-4">
    <h1 class="text-xl font-bold">納品書一覧</h1>
    <div class="flex flex-wrap gap-2 items-center">
        <form method="GET" class="flex items-center">
            <input type="month" name="month" value="{{ $month }}" class="border rounded p-2" onchange="this.form.submit()">
            @if ($month)
                <a href="{{ route('lumber-invoices.index') }}" class="ml-2 text-sm text-gray-500 underline">クリア</a>
            @endif
        </form>
        <a href="{{ route('lumber-invoices.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">+ 新規作成</a>
    </div>
</div>
<table class="w-full bg-white rounded shadow mobile-cards">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="p-3">納品書番号</th>
            <th class="p-3">日付</th>
            <th class="p-3">取引先</th>
            <th class="p-3 text-right">合計枚数</th>
            <th class="p-3 text-right">合計金額</th>
            <th class="p-3">状態</th>
            <th class="p-3"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoices as $invoice)
        <tr class="border-t">
            <td data-label="納品書番号" class="p-3">{{ $invoice->invoice_number }}</td>
            <td data-label="日付" class="p-3">{{ $invoice->issue_date->format('Y/m/d') }}</td>
            <td data-label="取引先" class="p-3">{{ $invoice->client->name }}</td>
            <td data-label="合計枚数" class="p-3 text-right">{{ number_format($invoice->lines->sum('total_quantity')) }}枚</td>
            <td data-label="合計金額" class="p-3 text-right">¥{{ number_format($invoice->total) }}</td>
            <td data-label="状態" class="p-3">{{ $invoice->status }}</td>
            <td data-label="操作" class="p-3 text-right">
                <a href="{{ route('lumber-invoices.pdf', $invoice) }}" target="_blank" class="text-green-600">PDF</a>
                <a href="{{ route('lumber-invoices.edit', $invoice) }}" class="text-blue-600 ml-2">編集</a>
                <form action="{{ route('lumber-invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 ml-2">削除</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $invoices->links() }}</div>
@endsection