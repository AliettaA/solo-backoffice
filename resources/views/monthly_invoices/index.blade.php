@extends('layouts.app')
@section('title', '月次請求書')
@section('content')
<h1 class="text-xl font-bold mb-6">月次請求書</h1>

<div class="bg-white p-6 rounded shadow mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">取引先</label>
            <select name="client_id" class="border rounded p-2" onchange="this.form.submit()">
                <option value="">選択してください</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected($clientId == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">対象月</label>
            <input type="month" name="month" value="{{ $month }}" class="border rounded p-2" onchange="this.form.submit()">
        </div>
    </form>
    <p class="text-sm text-gray-500 mt-2">対象期間: {{ \Carbon\Carbon::parse($start)->format('Y/m/d') }} 〜 {{ \Carbon\Carbon::parse($end)->format('Y/m/d') }}</p>
</div>

@if ($clientId && $summary)
<div class="bg-white p-6 rounded shadow mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-bold">対象納品書一覧({{ $summary['invoice_count'] }}件)</h2>
        <a href="{{ route('monthly-invoices.pdf', ['client_id' => $clientId, 'month' => $month]) }}" target="_blank"
           class="bg-green-600 text-white px-4 py-2 rounded text-sm">PDFを作成</a>
    </div>

    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">日付</th>
                <th class="p-2">納品書番号</th>
                <th class="p-2 text-right">金額</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $invoice)
            <tr class="border-t">
                <td class="p-2">{{ $invoice->issue_date->format('Y/m/d') }}</td>
                <td class="p-2">{{ $invoice->invoice_number }}</td>
                <td class="p-2 text-right">¥{{ number_format($invoice->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="border-t pt-4 text-right text-sm space-y-1">
        <div>小計(10%対象): ¥{{ number_format($summary['subtotal_10']) }}(消費税 ¥{{ number_format($summary['tax_10']) }})</div>
        @if ($summary['subtotal_8'] > 0)
        <div>小計(8%対象): ¥{{ number_format($summary['subtotal_8']) }}(消費税 ¥{{ number_format($summary['tax_8']) }})</div>
        @endif
        <div class="text-lg font-bold">合計金額: ¥{{ number_format($summary['total']) }}</div>
    </div>
</div>
@elseif ($clientId)
<p class="text-gray-500">この期間の納品書はありません。</p>
@endif
@endsection