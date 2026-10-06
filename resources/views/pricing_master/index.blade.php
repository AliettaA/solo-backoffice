@extends('layouts.app')
@section('title', '単価マスタ管理')
@section('content')
@if($errors->any())<div role="alert" class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h1 class="text-xl font-bold mb-6">単価マスタ管理</h1>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

{{-- 樹種一覧 --}}
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">樹種・基本単価</h2>

    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">樹種名</th>
                <th class="p-2">単価方式</th>
                <th class="p-2">固定単価</th>
                <th class="p-2">厚み加算</th>
                <th class="p-2">長さ調整</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($speciesList as $species)
            <tr class="border-t">
                <td class="p-2">{{ $species->name }}</td>
                <td class="p-2">{{ $species->pricing_type === 'by_width' ? '幅ごとの単価表' : '固定単価' }}</td>
                <td class="p-2">{{ $species->flat_price ? '¥'.number_format($species->flat_price) : '-' }}</td>
                <td class="p-2">{{ $species->apply_thickness_surcharge ? '適用' : '対象外' }}</td>
                <td class="p-2">{{ $species->apply_length_adjustment ? '適用' : '対象外' }}</td>
                <td class="p-2 text-right">
                    <a href="{{ route('pricing-master.species.edit', $species) }}" class="text-blue-600">編集</a>
                    <form action="{{ route('pricing-master.species.destroy', $species) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？(使用中の請求書がある場合は削除できません)')">
                        @csrf @method('DELETE')
                        <button class="text-red-600 ml-2">削除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <form action="{{ route('pricing-master.species.store') }}" method="POST" class="border-t pt-4 grid grid-cols-6 gap-2 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500">樹種名</label>
            <input type="text" name="name" required class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">単価方式</label>
            <select name="pricing_type" class="w-full border rounded p-2" onchange="this.form.flat_price.disabled = this.value !== 'flat'">
                <option value="by_width">幅ごとの単価表</option>
                <option value="flat">固定単価</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500">固定単価</label>
            <input type="number" name="flat_price" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">厚み加算</label>
            <select name="apply_thickness_surcharge" class="w-full border rounded p-2">
                <option value="1">適用</option>
                <option value="0">対象外</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500">長さ調整</label>
            <select name="apply_length_adjustment" class="w-full border rounded p-2">
                <option value="1">適用</option>
                <option value="0">対象外</option>
            </select>
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">+ 追加</button>
    </form>
</div>

{{-- 厚み加算 --}}
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">厚み加算ルール</h2>
    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">この厚み(mm)以上</th>
                <th class="p-2">加算額</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($thicknessSurcharges as $t)
            <tr class="border-t">
                <td class="p-2">{{ $t->min_thickness }}mm〜</td>
                <td class="p-2">+{{ number_format($t->surcharge_amount) }}円</td>
                <td class="p-2 text-right">
                    <form action="{{ route('pricing-master.thickness-surcharges.destroy', $t) }}" method="POST" onsubmit="return confirm('削除しますか？')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">削除</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <form action="{{ route('pricing-master.thickness-surcharges.store') }}" method="POST" class="border-t pt-4 flex gap-2 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500">この厚み(mm)以上</label>
            <input type="number" name="min_thickness" required class="border rounded p-2 w-32">
        </div>
        <div>
            <label class="block text-xs text-gray-500">加算額(円)</label>
            <input type="number" name="surcharge_amount" required class="border rounded p-2 w-32">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">+ 追加</button>
    </form>
</div>

{{-- 数量帯加算 --}}
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">数量帯加算ルール</h2>
    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">表示ラベル</th>
                <th class="p-2">下限枚数</th>
                <th class="p-2">上限枚数</th>
                <th class="p-2">加算額</th>
                <th class="p-2">並び順</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quantityTiers as $t)
            <tr class="border-t">
                <form action="{{ route('pricing-master.quantity-tiers.update', $t) }}" method="POST">
                    @csrf @method('PUT')
                    <td class="p-2"><input type="text" name="label" value="{{ $t->label }}" class="border rounded p-1 w-20"></td>
                    <td class="p-2"><input type="number" name="min_qty" value="{{ $t->min_qty }}" class="border rounded p-1 w-20"></td>
                    <td class="p-2"><input type="number" name="max_qty" value="{{ $t->max_qty }}" class="border rounded p-1 w-20" placeholder="上限なし"></td>
                    <td class="p-2"><input type="number" name="surcharge_amount" value="{{ $t->surcharge_amount }}" class="border rounded p-1 w-24"></td>
                    <td class="p-2"><input type="number" name="sort_order" value="{{ $t->sort_order }}" class="border rounded p-1 w-16"></td>
                    <td class="p-2 text-right whitespace-nowrap">
                        <button class="text-blue-600">保存</button>
                </form>
                        <form action="{{ route('pricing-master.quantity-tiers.destroy', $t) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 ml-2">削除</button>
                        </form>
                    </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <form action="{{ route('pricing-master.quantity-tiers.store') }}" method="POST" class="border-t pt-4 grid grid-cols-6 gap-2 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500">表示ラベル</label>
            <input type="text" name="label" required class="w-full border rounded p-2" placeholder="20-">
        </div>
        <div>
            <label class="block text-xs text-gray-500">下限枚数</label>
            <input type="number" name="min_qty" required class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">上限枚数</label>
            <input type="number" name="max_qty" class="w-full border rounded p-2" placeholder="上限なし">
        </div>
        <div>
            <label class="block text-xs text-gray-500">加算額</label>
            <input type="number" name="surcharge_amount" required class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">並び順</label>
            <input type="number" name="sort_order" class="w-full border rounded p-2">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">+ 追加</button>
    </form>
</div>

{{-- 長さ調整の設定値 --}}
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="font-bold mb-4">長さ調整の設定</h2>
    <form action="{{ route('pricing-master.settings.update') }}" method="POST" class="flex flex-wrap gap-4 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500">基準の長さ(m)</label>
            <input type="number" step="0.1" name="base_length_m" value="{{ $settings['base_length_m'] ?? 4 }}" class="border rounded p-2 w-32">
        </div>
        <div>
            <label class="block text-xs text-gray-500">基準長と同じ単価にする最短の長さ(m)</label>
            <input type="number" step="0.1" min="0.1" required name="base_price_min_length_m" value="{{ old('base_price_min_length_m', $settings['base_price_min_length_m'] ?? min(3.7, $settings['base_length_m'] ?? 4)) }}" class="border rounded p-2 w-32">
            @error('base_price_min_length_m')<p class="text-red-600 text-xs">基準の長さ以下で、0より大きい値を入力してください。</p>@enderror
        </div>
        <div>
            <label class="block text-xs text-gray-500">1mあたりの調整額(円)</label>
            <input type="number" step="0.1" name="price_per_meter_diff" value="{{ $settings['price_per_meter_diff'] ?? 10 }}" class="border rounded p-2 w-32">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">保存</button>
    </form>
    <p class="text-xs text-gray-500 mt-2">最短の長さから基準長までは同じ単価です（例：3.7〜4m）。範囲外は従来どおり「(実際の長さ - 基準長) × 調整額」で計算します。基準4m・調整額10円の場合、3mは-10円、2mは-20円です。</p>
</div>
@endsection