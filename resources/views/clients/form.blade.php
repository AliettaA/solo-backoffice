@extends('layouts.app')
@section('title', $client->exists ? '取引先編集' : '取引先登録')
@section('content')
<h1 class="text-xl font-bold mb-4">{{ $client->exists ? '取引先編集' : '取引先登録' }}</h1>
<form action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}" method="POST" class="bg-white p-6 rounded shadow space-y-4">
    @csrf
    @if ($client->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm mb-1">名称 <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $client->name) }}" class="w-full border rounded p-2">
        @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="invoice_registration_number" class="block text-sm mb-1">適格請求書発行事業者登録番号（任意）</label>
        <input id="invoice_registration_number" type="text" name="invoice_registration_number" value="{{ old('invoice_registration_number', $client->invoice_registration_number) }}" maxlength="14" pattern="T[0-9]{13}" placeholder="T1234567890123" class="w-full border rounded p-2">
        <p class="text-xs text-gray-500 mt-1">半角のT＋数字13桁で入力してください。未登録の場合は空欄にできます。</p>
        @error('invoice_registration_number') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm mb-1">敬称</label>
        <select name="honorific" class="w-full border rounded p-2">
            @foreach (['御中', '様', ''] as $h)
                <option value="{{ $h }}" @selected(old('honorific', $client->honorific) === $h)>{{ $h ?: 'なし' }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm mb-1">郵便番号</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}" class="w-full border rounded p-2">
    </div>

    <div>
        <label class="block text-sm mb-1">住所</label>
        <input type="text" name="address" value="{{ old('address', $client->address) }}" class="w-full border rounded p-2">
    </div>

    <div>
        <label class="block text-sm mb-1">担当者名</label>
        <input type="text" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}" class="w-full border rounded p-2">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">メール</label>
            <input type="email" name="email" value="{{ old('email', $client->email) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">電話番号</label>
            <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="w-full border rounded p-2">
        </div>
    </div>

    <div>
        <label class="block text-sm mb-1">メモ</label>
        <textarea name="memo" class="w-full border rounded p-2">{{ old('memo', $client->memo) }}</textarea>
    </div>

    <div class="flex gap-2">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">保存</button>
        <a href="{{ route('clients.index') }}" class="px-4 py-2 rounded border">キャンセル</a>
    </div>
</form>
@endsection