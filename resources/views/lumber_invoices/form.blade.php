@extends('layouts.app')
@section('title', $invoice->exists ? '請求書編集' : '請求書作成')
@section('content')
<h1 class="text-xl font-bold mb-4">{{ $invoice->exists ? '請求書編集' : '請求書作成' }}</h1>

@if($errors->any())<div class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form action="{{ $invoice->exists ? route('lumber-invoices.update', $invoice) : route('lumber-invoices.store') }}"
      method="POST" id="invoice-form">
    @csrf
    @if ($invoice->exists) @method('PUT') @endif

    <div class="bg-white p-6 rounded shadow space-y-4 mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">取引先 <span class="text-red-500">*</span></label>
                <select name="client_id" class="w-full border rounded p-2">
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $invoice->client_id) == $client->id)>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">日付 <span class="text-red-500">*</span></label>
                @php
                    $issueDateValue = $invoice->issue_date instanceof \Carbon\Carbon
                        ? $invoice->issue_date->format('Y-m-d')
                        : $invoice->issue_date;
                @endphp
                <input type="date" name="issue_date" value="{{ old('issue_date', $issueDateValue) }}" class="w-full border rounded p-2">
            </div>
        </div>
        <div>
            <label class="block text-sm mb-1">備考</label>
            <textarea name="notes" class="w-full border rounded p-2">{{ old('notes', $invoice->notes) }}</textarea>
        </div>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">明細</h2>
            <button type="button" id="add-line" class="bg-gray-700 text-white px-3 py-1 rounded text-sm">+ 行を追加</button>
        </div>
        <div id="lines-container" class="space-y-4"></div>
    </div>
    <div class="bg-white p-6 rounded shadow mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">その他商品(木材以外)</h2>
            <button type="button" id="add-product-line" class="bg-gray-700 text-white px-3 py-1 rounded text-sm">+ 商品を追加</button>
        </div>
    <div id="product-lines-container" class="space-y-2"></div>
</div>

    <div class="bg-white p-6 rounded shadow mb-6">
        <h2 class="font-bold mb-3">請求金額の調整（税抜・10%対象）</h2>
        <div class="grid grid-cols-2 gap-3">
            <label>調整内容<input type="text" name="invoice_adjustment_description" maxlength="100" value="{{ old('invoice_adjustment_description', $invoice->invoice_adjustment_description) }}" placeholder="例：板買取、おまけ、値引き" class="block border rounded p-2 w-full"></label>
            <label>調整額（円）<input type="number" step="1" id="invoice-adjustment" name="invoice_adjustment_amount" value="{{ old('invoice_adjustment_amount', $invoice->invoice_adjustment_amount ?? 0) }}" class="block border rounded p-2 w-full"></label>
        </div>
        <p class="text-sm text-gray-500 mt-2">値引きは -500 など、加算は 500 などを入力します。税抜小計に反映してから消費税を計算し、内容と金額を請求書に表示します。</p>
    </div>
    <div class="bg-white p-6 rounded shadow mb-6 text-right">
        <div class="text-sm text-gray-500">小計: <span id="grand-subtotal">¥0</span></div>
        <div class="text-sm text-gray-500">消費税(10%): <span id="grand-tax">¥0</span></div>
        <div class="text-lg font-bold">合計: <span id="grand-total">¥0</span></div>
    </div>

    <div class="flex gap-2">
        <button class="bg-blue-600 text-white px-6 py-2 rounded">保存</button>
        <a href="{{ route('lumber-invoices.index') }}" class="px-6 py-2 rounded border">キャンセル</a>
    </div>
</form>

<script>
window.SPECIES = @json($speciesData);
window.WIDTH_PRICES = @json($widthPrices);
window.THICKNESS_SURCHARGES = @json($thicknessSurcharges);
window.TIERS = @json($tiersData);
window.PRICING_SETTINGS = @json($pricingSettings);
@php
$restoredLines = collect(old('lines', []))->map(function ($line) use ($tiersData) {
    $quantities = [];
    foreach ($tiersData as $tier) $quantities[$tier['label']] = $line['quantities'][$tier['id']] ?? 0;
    $line['quantities'] = $quantities;
    return $line;
})->values()->all();
@endphp
window.EXISTING_LINES = @json(old('lines') !== null ? $restoredLines : $existingLines);
window.PRODUCTS = @json($productsData);
window.EXISTING_PRODUCT_LINES = @json(array_values(old('product_lines', $existingProductLines)));
</script>
<script src="{{ asset('js/lumber-invoice-form.js') }}?v=20260924-returned"></script>
@endsection