@extends('layouts.app')
@section('title', '事業者情報')
@section('content')
<h1 class="text-xl font-bold mb-6">事業者情報</h1>

<form action="{{ route('company.update') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-6">
    @csrf @method('PUT')

    <div>
        <h2 class="font-bold mb-3">基本情報</h2>
        <div class="space-y-4">
            <div>
                <label class="block text-sm mb-1">屋号・氏名 <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" class="w-full border rounded p-2">
                @error('name') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm mb-1">代表者名</label>
                <input type="text" name="representative_name" value="{{ old('representative_name', $company->representative_name) }}" class="w-full border rounded p-2">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">郵便番号</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">住所</label>
                    <input type="text" name="address" value="{{ old('address', $company->address) }}" class="w-full border rounded p-2">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">電話番号</label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm mb-1">メールアドレス</label>
                    <input type="email" name="email" value="{{ old('email', $company->email) }}" class="w-full border rounded p-2">
                </div>
            </div>
        </div>
    </div>

    <div class="border-t pt-6">
        <h2 class="font-bold mb-3">インボイス制度</h2>
        <div>
            <label class="block text-sm mb-1">適格請求書発行事業者 登録番号</label>
            <input type="text" name="invoice_registration_number" value="{{ old('invoice_registration_number', $company->invoice_registration_number) }}" placeholder="T1234567890123" class="w-full border rounded p-2">
        </div>
    </div>

    <div class="border-t pt-6">
        <h2 class="font-bold mb-3">振込先口座(請求書に印字されます)</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">銀行名</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $company->bank_name) }}" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm mb-1">支店名</label>
                <input type="text" name="bank_branch" value="{{ old('bank_branch', $company->bank_branch) }}" class="w-full border rounded p-2">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-sm mb-1">口座種別</label>
                <select name="bank_account_type" class="w-full border rounded p-2">
                    <option value="普通" @selected(old('bank_account_type', $company->bank_account_type) === '普通')>普通</option>
                    <option value="当座" @selected(old('bank_account_type', $company->bank_account_type) === '当座')>当座</option>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">口座番号</label>
                <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $company->bank_account_number) }}" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm mb-1">口座名義</label>
                <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $company->bank_account_holder) }}" class="w-full border rounded p-2">
            </div>
        </div>
    </div>

    <div class="border-t pt-6">
        <h2 class="font-bold mb-3">角印(任意)</h2>
        @if ($company->seal_image_path)
            <img src="{{ Storage::url($company->seal_image_path) }}" class="w-24 h-24 object-contain border rounded mb-2">
        @endif
        <input type="file" name="seal_image" accept="image/*" class="w-full border rounded p-2">
    </div>

    <button class="bg-blue-600 text-white px-6 py-2 rounded">保存</button>
</form>
@endsection