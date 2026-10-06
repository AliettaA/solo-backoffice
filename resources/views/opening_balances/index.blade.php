@extends('layouts.app')
@section('title', '期首残高設定')
@section('content')
<h1 class="text-xl font-bold mb-6">期首残高設定</h1>
<p class="text-sm text-gray-500 mb-4">基準日の取引を記帳する前の残高を登録します。決算書用には対象年の1月1日を指定してください。資産は通常「借方」、負債・元入金は通常「貸方」です。貸方残高は一覧でマイナス表示されます。科目の追加・分類は決算書画面から行えます。</p>

@if($errors->any())<div class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

<div class="bg-white p-6 rounded shadow mb-6">
    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">基準日</th>
                <th class="p-2">勘定科目</th>
                <th class="p-2 text-right">残高</th>
                <th class="p-2">メモ</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($balances as $balance)
            <tr class="border-t">
                <td class="p-2">{{ $balance->effective_date->format('Y/m/d') }}</td>
                <td class="p-2">{{ $balance->category->name }}</td>
                <td class="p-2 text-right">¥{{ number_format($balance->amount) }}</td>
                <td class="p-2">{{ $balance->memo }}</td>
                <td class="p-2 text-right">
                    <form action="{{ route('opening-balances.destroy', $balance) }}" method="POST" onsubmit="return confirm('削除しますか？')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">削除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <form action="{{ route('opening-balances.store') }}" method="POST" class="border-t pt-4 grid grid-cols-5 gap-2 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500">基準日</label>
            <input type="date" name="effective_date" required value="{{ now()->startOfYear()->toDateString() }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">勘定科目</label>
            <select name="expense_category_id" required class="w-full border rounded p-2">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500">残高</label>
            <select name="balance_side" class="w-full border rounded p-2"><option value="debit">借方（資産）</option><option value="credit">貸方（負債・元入金）</option></select>
            <input type="number" name="amount" min="0" required class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">メモ</label>
            <input type="text" name="memo" class="w-full border rounded p-2">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">登録</button>
    </form>
</div>
@endsection