@extends('layouts.app')
@section('title', $species->name.' の編集')
@section('content')
@if($errors->any())<div role="alert" class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h1 class="text-xl font-bold mb-6">{{ $species->name }} の編集</h1>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">基本設定</h2>
    <form action="{{ route('pricing-master.species.update', $species) }}" method="POST" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm mb-1">樹種名</label>
            <input type="text" name="name" value="{{ $species->name }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">単価方式</label>
            <select name="pricing_type" class="w-full border rounded p-2">
                <option value="by_width" @selected($species->pricing_type === 'by_width')>幅ごとの単価表</option>
                <option value="flat" @selected($species->pricing_type === 'flat')>固定単価</option>
            </select>
        </div>
        <div>
            <label class="block text-sm mb-1">固定単価(固定単価方式の場合)</label>
            <input type="number" name="flat_price" value="{{ $species->flat_price }}" class="w-full border rounded p-2">
        </div>
        <div class="flex gap-6">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="apply_thickness_surcharge" value="1" @checked($species->apply_thickness_surcharge)>
                厚み加算を適用する
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="apply_length_adjustment" value="1" @checked($species->apply_length_adjustment)>
                長さ調整を適用する
            </label>
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">保存</button>
    </form>
</div>

@if ($species->pricing_type === 'by_width')
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">幅ごとの基本単価</h2>
    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">幅(cm)</th>
                <th class="p-2">基本単価</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($species->widthPrices->sortBy('width') as $wp)
            <tr class="border-t">
                <td class="p-2">{{ rtrim(rtrim(number_format($wp->width, 1), '0'), '.') }}cm</td>
                <td class="p-2">¥{{ number_format($wp->base_price) }}</td>
                <td class="p-2 text-right">
                    <form action="{{ route('pricing-master.width-prices.destroy', $wp) }}" method="POST" onsubmit="return confirm('削除しますか？')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">削除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <form action="{{ route('pricing-master.width-prices.store', $species) }}" method="POST" class="border-t pt-4 flex gap-2 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500">幅(cm)</label>
            <input type="number" step="0.5" name="width" required class="border rounded p-2 w-32">
        </div>
        <div>
            <label class="block text-xs text-gray-500">基本単価(円)</label>
            <input type="number" name="base_price" required class="border rounded p-2 w-32">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">+ 追加/更新</button>
    </form>
    <p class="text-xs text-gray-500 mt-2">同じ幅を入力すると、既存の単価が上書きされます。</p>
</div>
@endif

<a href="{{ route('pricing-master.index') }}" class="text-blue-600">← 単価マスタ一覧に戻る</a>
@endsection