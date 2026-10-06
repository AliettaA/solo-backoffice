@extends('layouts.app')
@section('title', '取引先一覧')
@section('content')
@if($errors->any())<div role="alert" class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="flex justify-between items-center mb-4">
    <h1 class="text-xl font-bold">取引先一覧</h1>
    <a href="{{ route('clients.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">+ 新規登録</a>
</div>
<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-100 text-left text-sm">
        <tr>
            <th class="p-3">名称</th>
            <th class="p-3">登録番号（インボイス）</th>
            <th class="p-3">担当者</th>
            <th class="p-3">連絡先</th>
            <th class="p-3"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($clients as $client)
        <tr class="border-t">
            <td class="p-3">{{ $client->name }} {{ $client->honorific }}</td>
            <td class="p-3 text-sm whitespace-nowrap">{{ $client->invoice_registration_number ?: '—' }}</td>
            <td class="p-3">{{ $client->contact_person }}</td>
            <td class="p-3">{{ $client->email }} {{ $client->phone }}</td>
            <td class="p-3 text-right">
                <a href="{{ route('clients.edit', $client) }}" class="text-blue-600">編集</a>
                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">
                    @csrf @method('DELETE')
                    <button class="text-red-600 ml-2">削除</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $clients->links() }}</div>
@endsection