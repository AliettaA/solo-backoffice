@extends('layouts.app')
@section('title', '支払先マスタ')
@section('content')
@if($errors->any())<div role="alert" class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h1 class="text-xl font-bold mb-6">支払先マスタ</h1>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

<div class="bg-white p-6 rounded shadow mb-6">
    <table class="w-full text-sm mb-4">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">支払先名</th>
                <th class="p-2">メモ</th>
                <th class="p-2"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payees as $payee)
            <tr class="border-t">
                <form action="{{ route('payees.update', $payee) }}" method="POST">
                    @csrf @method('PUT')
                    <td class="p-2"><input type="text" name="name" value="{{ $payee->name }}" class="border rounded p-1 w-full"></td>
                    <td class="p-2"><input type="text" name="memo" value="{{ $payee->memo }}" class="border rounded p-1 w-full"></td>
                    <td class="p-2 text-right whitespace-nowrap">
                        <button class="text-blue-600">保存</button>
                </form>
                        <form action="{{ route('payees.destroy', $payee) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 ml-2">削除</button>
                        </form>
                    </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <form action="{{ route('payees.store') }}" method="POST" class="border-t pt-4 flex gap-2 items-end">
        @csrf
        <div class="flex-1">
            <label class="block text-xs text-gray-500">支払先名</label>
            <input type="text" name="name" required class="w-full border rounded p-2">
        </div>
        <div class="flex-1">
            <label class="block text-xs text-gray-500">メモ</label>
            <input type="text" name="memo" class="w-full border rounded p-2">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">+ 追加</button>
    </form>
</div>
@endsection