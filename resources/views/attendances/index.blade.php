@extends('layouts.app')
@section('title', '勤怠管理')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-xl font-bold">勤怠管理</h1>
    <form method="GET" class="flex items-center">
        @if($selectedEmployeeId)<input type="hidden" name="employee_id" value="{{ $selectedEmployeeId }}">@endif
        <input type="month" name="month" value="{{ $month }}" class="border rounded p-2" onchange="this.form.submit()">
    </form>
</div>

@if($selectedEmployee)
<div class="mb-4"><a href="{{ route('attendances.pdf', ['month' => $month, 'employee_id' => $selectedEmployeeId]) }}" target="_blank" rel="noopener" class="inline-block bg-blue-600 text-white rounded px-4 py-2">{{ $selectedEmployee->name }}さんの勤怠・給与PDF</a></div>
@else
<p class="text-sm text-gray-600 mb-4">従業員のタブを選ぶと、その月の勤怠・給与PDFを表示できます。</p>
@endif

<nav aria-label="従業員別の勤怠" class="flex gap-2 overflow-x-auto border-b mb-6 pb-2 text-sm">
    <a href="{{ route('attendances.index', ['month' => $month]) }}" @if(!$selectedEmployeeId) aria-current="page" @endif class="whitespace-nowrap rounded-t px-4 py-2 {{ !$selectedEmployeeId ? 'bg-blue-600 text-white font-bold' : 'bg-white text-gray-700 hover:bg-blue-50' }}">全員</a>
    @foreach($tabEmployees as $tabEmployee)
    <a href="{{ route('attendances.index', ['month' => $month, 'employee_id' => $tabEmployee->id]) }}" @if($selectedEmployeeId === $tabEmployee->id) aria-current="page" @endif class="whitespace-nowrap rounded-t px-4 py-2 {{ $selectedEmployeeId === $tabEmployee->id ? 'bg-blue-600 text-white font-bold' : 'bg-white text-gray-700 hover:bg-blue-50' }}">{{ $tabEmployee->name }}@if(!$tabEmployee->is_active)（退職）@endif</a>
    @endforeach
</nav>
@if($errors->any())<div class="mb-4 p-3 bg-red-100 text-red-800 rounded">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

@if (session('status'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
@endif

<div class="space-y-6 mb-6">
    <div class="bg-white p-4 rounded shadow overflow-x-auto">
        <h2 class="font-bold mb-3 text-sm">従業員別集計・源泉所得税の目安</h2>
        <p class="text-sm text-gray-600 mb-3">{{ substr($month, 0, 4) }}年の月額表・甲欄、現在の従業員マスタの扶養人数、社会保険料等0円での試算です。給与の支払い登録は行いません。実際の支払日・社会保険料等は給与支払い画面で確認してください。</p>
        <table class="w-full text-sm">
            <thead class="text-left bg-gray-100"><tr><th class="p-2">従業員</th><th>労働時間</th><th>扶養人数</th><th>支給額</th><th>源泉所得税（目安）</th><th>差引支給額（目安）</th></tr></thead>
            <tbody>
            @forelse ($employeeSummary as $data)
            <tr class="border-t">
                <td class="p-2">{{ $data['name'] }}</td>
                <td>{{ floor($data['minutes'] / 60) }}h{{ $data['minutes'] % 60 }}m</td>
                <td>{{ $data['dependents'] }}人</td>
                <td>¥{{ number_format($data['amount']) }}</td>
                <td>@if($data['tax'] !== null)¥{{ number_format($data['tax']) }}@else<span class="text-red-600">{{ $data['error'] }}</span>@endif</td>
                <td>{{ $data['net'] === null ? '—' : '¥'.number_format($data['net']) }}</td>
            </tr>
            @empty<tr><td colspan="6" class="p-3">この月の集計対象者はいません。</td></tr>@endforelse
            </tbody>
        </table>
        <a href="{{ route('salary-payments.index', ['month' => $month]) }}" class="inline-block mt-4 text-blue-600">{{ $month }} の給与支払い・源泉徴収を確認 →</a>
    </div>

    <div class="bg-white p-4 rounded shadow overflow-x-auto">
        <h2 class="font-bold mb-3 text-sm">{{ $selectedEmployee ? $selectedEmployee->name.'さんの勤怠' : '全員の勤怠' }}（{{ $month }}）</h2>
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="p-2">日付</th>
                    <th class="p-2">従業員</th>
                    <th class="p-2">出勤</th>
                    <th class="p-2">退勤</th>
                    <th class="p-2">休憩</th>
                    <th class="p-2 text-right">労働時間</th>
                    <th class="p-2 text-right">金額</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                <tr class="border-t">
                    <td class="p-2">
                        <input type="date" form="attendance-form-{{ $attendance->id }}" name="work_date" value="{{ $attendance->work_date->format('Y-m-d') }}" class="border rounded p-1 w-32">
                    </td>
                    <td class="p-2">{{ $attendance->employee->name }}</td>
                    <td class="p-2">
                        <input type="time" form="attendance-form-{{ $attendance->id }}" name="start_time" value="{{ \Carbon\Carbon::parse($attendance->start_time)->format('H:i') }}" class="border rounded p-1 w-24">
                    </td>
                    <td class="p-2">
                        <input type="time" form="attendance-form-{{ $attendance->id }}" name="end_time" value="{{ \Carbon\Carbon::parse($attendance->end_time)->format('H:i') }}" class="border rounded p-1 w-24">
                    </td>
                    <td class="p-2">
                        <input type="number" form="attendance-form-{{ $attendance->id }}" name="break_minutes" value="{{ $attendance->break_minutes }}" class="border rounded p-1 w-16">
                    </td>
                    <td class="p-2 text-right">{{ floor($attendance->worked_minutes / 60) }}h{{ $attendance->worked_minutes % 60 }}m</td>
                    <td class="p-2 text-right">¥{{ number_format($attendance->amount) }}</td>
                    <td class="p-2 text-right whitespace-nowrap">
                        <input type="hidden" form="attendance-form-{{ $attendance->id }}" name="employee_id" value="{{ $attendance->employee_id }}">
                        <button form="attendance-form-{{ $attendance->id }}" class="text-blue-600">保存</button>
                        <form action="{{ route('attendances.destroy', $attendance) }}" method="POST" class="inline" onsubmit="return confirm('削除しますか？')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="selected_employee_id" value="{{ $selectedEmployeeId }}">
                            <button class="text-red-600 ml-2">削除</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="p-4 text-center text-gray-500">この月の勤怠記録はありません</td></tr>
                @endforelse
            </tbody>
        </table>

        @foreach ($attendances as $attendance)
        <form id="attendance-form-{{ $attendance->id }}" action="{{ route('attendances.update', $attendance) }}" method="POST" style="display:none;">
            @csrf @method('PUT')
            <input type="hidden" name="selected_employee_id" value="{{ $selectedEmployeeId }}">
        </form>
        @endforeach
    </div>
</div>

<div class="bg-white p-6 rounded shadow">
    <h2 class="font-bold mb-4">勤怠を記録</h2>
    <form action="{{ route('attendances.store') }}" method="POST" class="grid grid-cols-6 gap-2 items-end">
        @csrf
        <input type="hidden" name="selected_employee_id" value="{{ $selectedEmployeeId }}">
        <div>
            <label class="block text-xs text-gray-500">従業員</label>
            <select name="employee_id" required class="w-full border rounded p-2">
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(old('employee_id', $selectedEmployeeId) == $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500">日付</label>
            <input type="date" name="work_date" required value="{{ old('work_date', $month === now()->format('Y-m') ? now()->toDateString() : $month.'-01') }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">出勤時刻</label>
            <input type="time" name="start_time" required value="07:30" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">退勤時刻</label>
            <input type="time" name="end_time" required value="16:45" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500">休憩(分)</label>
            <input type="number" name="break_minutes" value="75" class="w-full border rounded p-2">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">記録</button>
    </form>
</div>
@endsection