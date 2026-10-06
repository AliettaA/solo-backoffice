<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><style>
@page { margin: 28px 32px; }
@font-face {font-family:ipaex;src:url('{{ resource_path('fonts/ipaexg.ttf') }}') format('truetype');}
body {font-family:ipaex,sans-serif;font-size:12px;color:#222;line-height:1.25;}
h1 {font-size:22px;text-align:center;margin:0 0 6px;} .period{text-align:center;margin:0 0 16px;font-size:14px;}
table{width:100%;border-collapse:collapse;table-layout:fixed;} th,td{border:1px solid #aaa;padding:4px 5px;} th{background:#eef1f4;font-weight:normal;} .num{text-align:right;} .center{text-align:center;} tr{page-break-inside:avoid;} .summary{margin-top:16px;page-break-inside:avoid;} .summary td,.summary th{padding:7px;} .net{background:#e4ebf1;font-size:15px;} .note{font-size:11px;margin:8px 0;}
</style></head><body>
@php($duration = fn($m) => intdiv((int)$m,60).'時間'.((int)$m%60).'分')
<h1>{{ $employee->name }} 様</h1><p class="period">{{ substr($month,0,4) }}年{{ (int)substr($month,5,2) }}月 勤怠・給与明細</p>
<table><thead><tr><th>日付</th><th>開始時間</th><th>就業時間<br>（退勤）</th><th>休憩時間</th><th>1日の勤務時間</th><th>1日の給料</th></tr></thead><tbody>
@forelse($attendances as $attendance)
<tr><td class="center">{{ $attendance->work_date->format('m/d') }}（{{ ['日','月','火','水','木','金','土'][$attendance->work_date->dayOfWeek] }}）</td><td class="center">{{ substr($attendance->start_time,0,5) }}</td><td class="center">{{ substr($attendance->end_time,0,5) }}</td><td class="center">{{ $attendance->break_minutes }}分</td><td class="center">{{ $duration($attendance->worked_minutes) }}</td><td class="num">{{ $employee->employment_type === 'family' ? '月額固定' : number_format($attendance->amount).'円' }}</td></tr>
@empty<tr><td colspan="6" class="center">この月の勤怠記録はありません。</td></tr>@endforelse
</tbody></table>
<div class="summary"><table><tr><th>1か月の合計勤務時間</th><td class="num">{{ $duration($minutes) }}</td><th>時給</th><td class="num">{{ $employee->employment_type === 'family' ? '月額固定' : number_format($employee->hourly_wage).'円' }}</td></tr><tr><th colspan="2">総額</th><td colspan="2" class="num">{{ number_format($gross) }}円</td></tr><tr><th colspan="2">市県民税</th><td colspan="2" class="num">{{ $residentTax === null ? '未登録' : number_format($residentTax).'円' }}</td></tr><tr><th colspan="2">源泉徴収額</th><td colspan="2" class="num">{{ $tax === null ? '未登録' : number_format($tax).'円' }}</td></tr>
@if($insurance)<tr><th colspan="2">社会保険料等</th><td colspan="2" class="num">{{ number_format($insurance) }}円</td></tr>@endif
<tr class="net"><th colspan="2">差引支給額</th><td colspan="2" class="num">{{ $net === null ? '未確定' : number_format($net).'円' }}</td></tr></table>
@if($registered && $gross != $workedGross)<p class="note">現在の勤怠に基づく支給額（{{ number_format($workedGross) }}円）と給与支払い登録の総額が異なります。</p>@endif
</div></body></html>
