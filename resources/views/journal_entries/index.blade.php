@extends('layouts.app')
@section('title', '振替伝票')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-xl font-bold">振替伝票</h1>
    <div class="flex gap-2 items-center">
        <form method="GET" class="flex items-center">
            <input type="month" name="month" value="{{ $month }}" class="border rounded p-2" onchange="this.form.submit()">
        </form>
        <a href="{{ route('journal-entries.pdf', ['month' => $month]) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded text-sm">PDF出力</a>
        <a href="{{ route('journal-entries.create', ['month' => $month]) }}" class="bg-blue-600 text-white px-4 py-2 rounded">+ 伝票を作成</a>
    </div>
</div>

@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

@forelse ($entries as $entry)
<div class="bg-white p-4 rounded shadow mb-3">
    <div class="flex justify-between items-center mb-2">
        <div>
            <span class="font-bold">{{ $entry->entry_date->format('Y/m/d') }}</span>
            @if ($entry->description)
                <span class="text-gray-500 ml-2">{{ $entry->description }}</span>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('journal-entries.edit', $entry) }}" class="text-blue-600">編集</a>
            <form action="{{ route('journal-entries.destroy', $entry) }}" method="POST" onsubmit="return confirm('削除しますか？')">
                @csrf @method('DELETE')
                <button class="text-red-600">削除</button>
            </form>
        </div>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2 w-1/2">借方</th>
                <th class="p-2 w-1/2">貸方</th>
            </tr>
        </thead>
        <tbody>
            @php
                $debits = $entry->lines->where('side', 'debit')->values();
                $credits = $entry->lines->where('side', 'credit')->values();
                $rowCount = max($debits->count(), $credits->count());
            @endphp
            @for ($i = 0; $i < $rowCount; $i++)
            <tr class="border-t">
                <td class="p-2">
                    @if ($debits->get($i))
                        {{ $debits[$i]->category->name }}
                        @if ($debits[$i]->counterparty_name)({{ $debits[$i]->counterparty_name }})@endif
                        ¥{{ number_format($debits[$i]->amount) }}
                    @endif
                </td>
                <td class="p-2">
                    @if ($credits->get($i))
                        {{ $credits[$i]->category->name }}
                        @if ($credits[$i]->counterparty_name)({{ $credits[$i]->counterparty_name }})@endif
                        ¥{{ number_format($credits[$i]->amount) }}
                    @endif
                </td>
            </tr>
            @endfor
        </tbody>
    </table>
</div>
@empty
<p class="text-gray-500">この月の伝票はありません。</p>
@endforelse
@endsection