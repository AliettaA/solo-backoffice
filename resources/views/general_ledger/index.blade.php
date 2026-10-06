@extends('layouts.app')
@section('title', '総勘定元帳')
@section('content')
<h1 class="text-xl font-bold mb-6">総勘定元帳</h1>

<div class="bg-white p-6 rounded shadow mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">年</label>
            <select name="year" class="border rounded p-2" onchange="this.form.submit()">
                @for ($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}年</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">勘定科目</label>
            <select name="category_id" class="border rounded p-2" onchange="this.form.submit()">
                <option value="">選択してください</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('general-ledger.pdf', ['year' => $year]) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded text-sm">全科目PDF出力({{ $year }}年)</a>
    </form>
</div>

@if ($ledger)
<div class="bg-white p-6 rounded shadow">
    <h2 class="font-bold mb-4">{{ isset($ledger['deposit_ledgers']) ? '預り金・市県民税預り金' : $ledger['category']->name }}({{ $year }}年)</h2>

    @if(isset($ledger['deposit_ledgers']))
        @include('general_ledger.deposit_ledgers', ['showEdit' => true])
    @elseif($ledger['withholding_sections'])
        @include('general_ledger.withholding_sections', ['showEdit' => true])
    @elseif($ledger['payable_sections'])
        @include('general_ledger.payable_sections', ['showEdit' => true])
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-2">日付</th>
                <th class="p-2">項目</th>
                <th class="p-2">支払い先</th>
                <th class="p-2 text-right">金額</th>
                <th class="p-2 text-right">合計</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-t bg-gray-50">
                <td class="p-2" colspan="4">前期繰越</td>
                <td class="p-2 text-right font-bold">¥{{ number_format($ledger['opening']) }}</td>
            </tr>
            @foreach ($ledger['rows'] as $row)
            <tr class="border-t">
                <td class="p-2">{{ $row['date']->format('n/j') }} @include('general_ledger.edit_link', ['showEdit' => true])</td>
                <td class="p-2">{{ $row['item'] }}</td>
                <td class="p-2">{{ $row['payee'] }}</td>
                <td class="p-2 text-right">¥{{ number_format($row['amount']) }}</td>
                <td class="p-2 text-right">¥{{ number_format($row['balance']) }}</td>
            </tr>
            @endforeach
            <tr class="border-t bg-gray-50 font-bold">
                <td class="p-2" colspan="4">期末残高</td>
                <td class="p-2 text-right">¥{{ number_format($ledger['closing']) }}</td>
            </tr>
        </tbody>
    </table>
    @endif
</div>
@endif
@endsection