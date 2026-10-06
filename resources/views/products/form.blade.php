@extends('layouts.app')
@section('title', $product->exists ? '品目編集' : '品目登録')
@section('content')
<h1 class="text-xl font-bold mb-4">{{ $product->exists ? '品目編集' : '品目登録' }}</h1>
<form action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" method="POST" class="bg-white p-6 rounded shadow space-y-4">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm mb-1">品目名 <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="w-full border rounded p-2">
        @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm mb-1">説明</label>
        <textarea name="description" class="w-full border rounded p-2">{{ old('description', $product->description) }}</textarea>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm mb-1">単価 <span class="text-red-500">*</span></label>
            <input type="number" name="unit_price" value="{{ old('unit_price', $product->unit_price) }}" class="w-full border rounded p-2">
            @error('unit_price') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm mb-1">単位</label>
            <input type="text" name="unit" value="{{ old('unit', $product->unit ?? '式') }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">税率 <span class="text-red-500">*</span></label>
            <select name="tax_rate" class="w-full border rounded p-2">
                <option value="10" @selected(old('tax_rate', $product->tax_rate ?? 10) == 10)>10%（標準）</option>
                <option value="8" @selected(old('tax_rate', $product->tax_rate ?? 10) == 8)>8%（軽減）</option>
            </select>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">保存</button>
        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded border">キャンセル</a>
    </div>
</form>
@endsection