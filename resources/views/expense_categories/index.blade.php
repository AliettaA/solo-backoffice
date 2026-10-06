@extends('layouts.app')
@section('title', '勘定科目管理')
@section('content')
<h1 class="text-xl font-bold mb-4">勘定科目管理</h1>
<p class="text-sm mb-4">登録した科目は振替伝票の借方で選択できます。決算書の分類と表示順も設定できます。使用中・自動処理用の科目は、名称変更と削除を制限しています。</p>
@if($errors->any())<div class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@php($types = \App\Services\FinancialStatements::TYPES)
<form method="POST" action="{{ route('expense-categories.store') }}" class="bg-white rounded shadow p-4 mb-6 flex flex-wrap gap-3 items-end">
@csrf
<label>新しい科目名<input name="name" required maxlength="50" class="block border rounded p-2" placeholder="例：売掛金"></label>
<label>決算書の分類<select name="statement_type" class="block border rounded p-2"><option value="">名前から自動判定</option>@foreach($types as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
<label>表示順<input name="sort_order" type="number" min="0" max="65535" value="100" class="block border rounded p-2 w-24"></label>
<button class="bg-blue-600 text-white rounded px-4 py-2">追加</button>
</form>
<div class="bg-white rounded shadow p-4">
@foreach($categories as $category)
@php($locked = in_array($category->id, $lockedIds))
<div class="border-b py-3 flex flex-wrap gap-3 items-center">
<form method="POST" action="{{ route('expense-categories.update', $category) }}" class="flex flex-wrap gap-3 items-end">
@csrf @method('PUT')
<label>科目名<input name="name" value="{{ $category->name }}" required maxlength="50" @readonly($locked) class="block border rounded p-2 {{ $locked ? 'bg-gray-100' : '' }}"></label>
<label>決算書の分類<select name="statement_type" class="block border rounded p-2"><option value="">未分類／自動判定</option>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(($category->statement_type ?: \App\Services\FinancialStatements::defaultType($category->name)) === $value)>{{ $label }}</option>@endforeach</select></label>
<label>表示順<input name="sort_order" type="number" min="0" max="65535" value="{{ $category->sort_order }}" class="block border rounded p-2 w-24"></label>
<button class="border rounded px-4 py-2">保存</button>
</form>
@if(!$locked)<form method="POST" action="{{ route('expense-categories.destroy', $category) }}" onsubmit="return confirm('この勘定科目を削除しますか？')">@csrf @method('DELETE')<button class="text-red-600">削除</button></form>@else<span class="text-xs text-gray-500">使用中・自動処理用</span>@endif
</div>
@endforeach
</div>
@endsection
