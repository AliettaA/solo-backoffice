@extends('layouts.app')
@section('title', '入金管理')
@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-xl font-bold">入金管理</h1>
        <p class="text-sm text-gray-500">対象期間: {{ \Carbon\Carbon::parse($start)->format('Y/m/d') }} 〜 {{ \Carbon\Carbon::parse($end)->format('Y/m/d') }}</p>
    </div>
    <form method="GET" class="flex gap-2 items-center">
        <input type="month" name="month" value="{{ $month }}" class="border rounded p-2" onchange="this.form.submit()">
    </form>
</div>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

<p class="text-sm text-gray-600 mb-4">銀行振込・現金の入金を記録すると、入金日で振替伝票を自動作成し、対応する出納帳に反映します（相手科目：売上）。「その他」は自動作成の対象外です。</p>
@if($errors->any())<div class="mb-4 p-3 bg-red-100">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

<section class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">新規入金登録</h2>
    @if($clients->isEmpty())
        <p>先に<a href="{{ route('clients.create') }}" class="text-blue-600 underline">取引先を登録</a>してください。</p>
    @else
    <form action="{{ route('payments.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label for="new-payment-client" class="block text-sm mb-1">取引先</label>
            <select id="new-payment-client" name="client_id" required class="w-full border rounded p-2">
                <option value="">選択してください</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', null) == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="new-payment-month" class="block text-sm mb-1">請求対象月</label>
            <input id="new-payment-month" type="month" name="target_month" required value="{{ old('target_month', $month) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label for="new-payment-date" class="block text-sm mb-1">入金日</label>
            <input id="new-payment-date" type="date" name="paid_at" required value="{{ old('paid_at', now()->toDateString()) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label for="new-payment-amount" class="block text-sm mb-1">入金額（円）</label>
            <input id="new-payment-amount" type="number" name="amount" min="1" step="1" required value="{{ old('amount') }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label for="new-payment-method" class="block text-sm mb-1">入金方法</label>
            <select id="new-payment-method" name="method" required class="w-full border rounded p-2">
                @foreach(['銀行振込', '現金', 'その他'] as $method)
                    <option value="{{ $method }}" @selected(old('method', '銀行振込') === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="new-payment-memo" class="block text-sm mb-1">メモ</label>
            <input id="new-payment-memo" type="text" name="memo" value="{{ old('memo') }}" class="w-full border rounded p-2">
        </div>
        @if($errors->has('duplicate'))<label class="md:col-span-3 bg-amber-50 p-3"><input type="checkbox" name="confirm_duplicate" value="1" required> 同じ内容の記録とは別の入金であることを確認しました</label>@endif
        <div class="md:col-span-3"><button class="bg-blue-600 text-white px-4 py-2 rounded">入金を記録</button></div>
    </form>
    @endif
</section>

@forelse ($summaries as $s)
<div class="bg-white p-6 rounded shadow mb-4">
    <div class="flex justify-between items-start mb-3">
        <h2 class="font-bold text-lg">{{ $s['client']->name }}</h2>
        <div class="text-right text-sm">
            <div>請求合計: <span class="font-bold">¥{{ number_format($s['invoice_total']) }}</span></div>
            <div>入金合計: <span class="font-bold text-green-600">¥{{ number_format($s['payment_total']) }}</span></div>
            <div>残額: <span class="font-bold {{ $s['remaining'] > 0 ? 'text-red-600' : 'text-gray-400' }}">¥{{ number_format($s['remaining']) }}</span></div>
        </div>
    </div>

    @if ($s['payments']->count() > 0)
    <table class="w-full text-sm mb-3">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">入金日</th>
                <th class="p-2">金額</th>
                <th class="p-2">方法</th>
                <th class="p-2">メモ</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($s['payments'] as $payment)
            <tr class="border-t">
                <td class="p-2">{{ $payment->paid_at->format('Y/m/d') }}</td>
                <td class="p-2">¥{{ number_format($payment->amount) }}</td>
                <td class="p-2">{{ $payment->method }}</td>
                <td class="p-2">{{ $payment->memo }}</td>
                <td class="p-2 text-right">
                    <form action="{{ route('payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('削除しますか？')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">削除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <form action="{{ route('payments.store') }}" method="POST" class="border-t pt-3 flex gap-2 items-end">
        @csrf
        <input type="hidden" name="client_id" value="{{ $s['client']->id }}">
        <input type="hidden" name="target_month" value="{{ $month }}">
        <div>
            <label class="block text-xs text-gray-500">入金日</label>
            <input type="date" name="paid_at" required value="{{ now()->toDateString() }}" class="border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">金額</label>
            <input type="number" name="amount" required value="{{ $s['remaining'] > 0 ? $s['remaining'] : '' }}" class="border rounded p-2 w-32">
        </div>
        <div>
            <label class="block text-xs text-gray-500">方法</label>
            <select name="method" class="border rounded p-2">
                <option value="銀行振込">銀行振込</option>
                <option value="現金">現金</option>
                <option value="その他">その他</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500">メモ</label>
            <input type="text" name="memo" class="border rounded p-2">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">入金を記録</button>
    </form>
</div>
@empty
<p class="text-gray-500">この月の請求・入金データはありません。</p>
@endforelse
@endsection