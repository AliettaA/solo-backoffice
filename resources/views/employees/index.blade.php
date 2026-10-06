@extends('layouts.app')
@section('title', '従業員マスタ')
@section('content')
<h1 class="text-xl font-bold mb-4">従業員マスタ</h1>
<p class="text-sm text-gray-600 mb-4">専従者は月額80,000円、従業員は勤怠と時給で給与を計算します。扶養人数には源泉徴収の「扶養親族等の数」を入力してください（0〜7人）。月額表・甲欄を使用します。</p>
@if(session('status'))<p class="p-3 bg-green-100 mb-4">{{ session('status') }}</p>@endif
@if($errors->any())<div class="p-3 bg-red-100 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="bg-white rounded shadow p-4 overflow-x-auto">
<table class="w-full text-sm">
<thead><tr class="text-left"><th>氏名</th><th>区分</th><th>時給（従業員）</th><th>扶養人数</th><th>在籍</th><th></th></tr></thead>
<tbody>
@foreach($employees as $employee)
<tr class="border-t">
<td class="p-2"><input form="employee-{{ $employee->id }}" name="name" required value="{{ $employee->name }}" class="border rounded p-2"></td>
<td class="p-2"><select form="employee-{{ $employee->id }}" name="employment_type" class="border rounded p-2"><option value="employee" @selected($employee->employment_type === 'employee')>従業員</option><option value="family" @selected($employee->employment_type === 'family')>専従者（月額80,000円）</option></select></td>
<td class="p-2"><input form="employee-{{ $employee->id }}" type="number" name="hourly_wage" min="0" value="{{ $employee->hourly_wage }}" class="border rounded p-2 w-24"></td>
<td class="p-2"><input form="employee-{{ $employee->id }}" type="number" name="dependents" required min="0" max="7" value="{{ $employee->dependents }}" class="border rounded p-2 w-20"> 人</td>
<td class="p-2"><input form="employee-{{ $employee->id }}" type="checkbox" name="is_active" value="1" @checked($employee->is_active)></td>
<td class="p-2 whitespace-nowrap"><form id="employee-{{ $employee->id }}" action="{{ route('employees.update', $employee) }}" method="POST" class="inline">@csrf @method('PUT')<button class="text-blue-600">保存</button></form>
<form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">@csrf @method('DELETE')<button class="text-red-600 ml-2">削除</button></form></td>
</tr>
@endforeach
</tbody></table>
<form action="{{ route('employees.store') }}" method="POST" class="border-t mt-4 pt-4 flex flex-wrap gap-3 items-end">@csrf
<label>氏名<input name="name" required value="{{ old('name') }}" class="block border rounded p-2"></label>
<label>区分<select name="employment_type" class="block border rounded p-2"><option value="employee">従業員</option><option value="family" @selected(old('employment_type') === 'family')>専従者（月額80,000円）</option></select></label>
<label>時給（従業員）<input type="number" name="hourly_wage" min="0" value="{{ old('hourly_wage', 0) }}" class="block border rounded p-2 w-28"></label>
<label>扶養人数<input type="number" name="dependents" required min="0" max="7" value="{{ old('dependents', 0) }}" class="block border rounded p-2 w-20"></label>
<button class="bg-blue-600 text-white px-4 py-2 rounded">追加</button>
</form></div>
@endsection
