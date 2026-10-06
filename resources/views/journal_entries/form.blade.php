@extends('layouts.app')
@section('title', $entry->exists ? '伝票編集' : '伝票作成')
@section('content')
<h1 class="text-xl font-bold mb-4">{{ $entry->exists ? '伝票編集' : '伝票作成' }}</h1>

@if($errors->any())
<div class="mb-4 p-3 bg-red-100 text-red-800 rounded">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif

<form action="{{ $entry->exists ? route('journal-entries.update', $entry) : route('journal-entries.store') }}" method="POST" id="entry-form">
    @csrf
    @if(!empty($returnTo))<input type="hidden" name="return_to" value="{{ $returnTo }}">@endif
    @if ($entry->exists) @method('PUT') @endif

    <div class="bg-white p-6 rounded shadow space-y-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="target-month" class="block text-sm mb-1">対象年月 <span class="text-red-500">*</span></label>
                <input id="target-month" type="month" name="target_month" required value="{{ old('target_month', $month) }}" class="w-full border rounded p-2">
                <p class="text-sm text-gray-500 mt-1">この年月の日付だけ登録できます。</p>
            </div>
            <div>
                <label class="block text-sm mb-1">日付 <span class="text-red-500">*</span></label>
                @php
                    $entryDateValue = $entry->entry_date instanceof \Carbon\Carbon ? $entry->entry_date->format('Y-m-d') : $entry->entry_date;
                @endphp
                <input id="entry-date" required type="date" name="entry_date" value="{{ old('entry_date', $entryDateValue) }}" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm mb-1">摘要(任意)</label>
                <input type="text" name="description" value="{{ old('description', $entry->description) }}" placeholder="例: 7月分売上" class="w-full border rounded p-2">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white p-4 rounded shadow">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold">借方(左)</h2>
                <button type="button" id="add-debit" class="bg-gray-700 text-white px-3 py-1 rounded text-sm">+ 行を追加</button>
            </div>
            <div id="debit-container" class="space-y-2"></div>
            <div class="border-t mt-3 pt-2 text-right font-bold">借方合計: <span id="debit-total">¥0</span></div>
        </div>

        <div class="bg-white p-4 rounded shadow">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold">貸方(右)</h2>
                <button type="button" id="add-credit" class="bg-gray-700 text-white px-3 py-1 rounded text-sm">+ 行を追加</button>
            </div>
            <div id="credit-container" class="space-y-2"></div>
            <div class="border-t mt-3 pt-2 text-right font-bold">貸方合計: <span id="credit-total">¥0</span></div>
        </div>
    </div>

    <div id="balance-check" class="p-3 rounded mb-4 text-center font-bold"></div>

    <div class="flex gap-2">
        <button class="bg-blue-600 text-white px-6 py-2 rounded">保存</button>
        <a href="{{ $returnTo ?? route('journal-entries.index', ['month' => $month]) }}" class="px-6 py-2 rounded border">キャンセル</a>
    </div>
</form>

<script>
window.CATEGORIES = @json($categoriesData);
window.CREDIT_CATEGORIES = @json($creditCategoriesData);
window.PAYEES = @json($payeesData);
window.EXISTING_LINES = @json(array_values(old('lines', $existingLines)));
</script>
<script src="{{ asset('js/journal-entry-form.js') }}?v=20260923"></script>
<script>
const monthInput = document.getElementById('target-month');
const dateInput = document.getElementById('entry-date');
function restrictEntryDate(changedMonth = false) {
    const month = monthInput.value;
    if (!/^\d{4}-\d{2}$/.test(month)) {
        dateInput.disabled = true;
        return;
    }
    dateInput.disabled = false;
    const [year, number] = month.split('-').map(Number);
    const days = new Date(year, number, 0).getDate();
    dateInput.min = `${month}-01`;
    dateInput.max = `${month}-${days}`;
    // A month change requires choosing the date again; never silently move a voucher.
    if (changedMonth && dateInput.value.slice(0, 7) !== month) dateInput.value = '';
    dateInput.setCustomValidity(dateInput.value && dateInput.value.slice(0, 7) !== month ? '対象年月内の日付を選択してください。' : '');
}
monthInput.addEventListener('input', () => restrictEntryDate(true));
dateInput.addEventListener('input', () => restrictEntryDate());
restrictEntryDate();
</script>
@endsection