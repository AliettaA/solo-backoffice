@extends('layouts.app')
@section('title', '品目一覧')
@section('content')
@if($errors->any())<div role="alert" class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold">品目一覧</h1>
    <a href="{{ route('products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">+ 新規登録</a>
</div>
<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="p-3">品目名</th>
            <th class="p-3">単価</th>
            <th class="p-3">単位</th>
            <th class="p-3">税率</th>
            <th class="p-3"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($products as $product)
        <tr class="border-t">
            <td class="p-3">{{ $product->name }}</td>
            <td class="p-3">¥{{ number_format($product->unit_price) }}</td>
            <td class="p-3">{{ $product->unit }}</td>
            <td class="p-3">{{ $product->tax_rate }}%{{ $product->is_reduced_tax ? '（軽減）' : '' }}</td>
            <td class="p-3 text-right">
                <a href="{{ route('products.edit', $product) }}" class="text-blue-600">編集</a>
                <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 ml-2">削除</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $products->links() }}</div>
@endsection