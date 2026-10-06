@extends('layouts.app')
@section('title', '給与支払い')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-xl font-bold">給与支払い</h1><form method="GET"><input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" class="border rounded p-2"></form></div>
<p class="text-sm text-gray-600 mb-4">月1回払い・支払日の年の月額表（甲欄、2025年・2026年対応）で計算します。支払日の初期値は対象月の末日です。実際の支払日に変更してください。扶養控除等申告書の提出者が対象です。税額表の対応範囲は控除後332,000円未満、扶養0〜7人です。</p>
<p class="text-sm mb-4">源泉徴収額を計算した後、市県民税を差し引いて差引支給額を算出します。</p>
@if(session('status'))<p class="bg-green-100 p-3 mb-4">{{ session('status') }}</p>@endif
@if($errors->any())<div class="bg-red-100 p-3 mb-4">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@forelse($summaries as $s)
<div class="bg-white rounded shadow p-6 mb-4">
<h2 class="font-bold text-lg">{{ $s['employee']->name }} <span class="text-sm text-gray-600">{{ $s['employee']->employment_type === 'family' ? '専従者' : '従業員' }}・扶養 {{ $s['employee']->dependents }}人</span></h2>
<p class="my-3">{{ $s['employee']->employment_type === 'family' ? '月額給与' : '勤怠からの給与' }}：¥{{ number_format($s['worked_amount']) }} ／ 支払登録済み（控除前）：¥{{ number_format($s['paid_amount']) }}</p>
@if($s['payments']->isNotEmpty())
<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="text-left bg-gray-100"><tr><th class="p-2">支払日</th><th>支給額</th><th>市県民税</th><th>源泉徴収額</th><th>差引支給額</th><th>方法</th><th></th></tr></thead><tbody>
@foreach($s['payments'] as $payment)
<tr class="border-t"><td class="p-2">{{ $payment->paid_at->format('Y/m/d') }}</td><td>¥{{ number_format($payment->gross_amount ?: $payment->amount) }}</td><td>¥{{ number_format($payment->resident_tax) }}</td><td>¥{{ number_format($payment->withholding_tax) }}</td><td>¥{{ number_format($payment->amount) }}</td><td>{{ $payment->method }}</td><td><form action="{{ route('salary-payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('支払いと対応する仕訳を削除しますか？')">@csrf @method('DELETE')<button class="text-red-600">削除</button></form></td></tr>
@endforeach
</tbody></table></div>
<p class="text-sm text-gray-500 mt-3">この月は登録済みです。再計算する場合は記録を削除して再登録してください。</p>
@else
@php($restore = (string) old('employee_id') === (string) $s['employee']->id)
<form action="{{ route('salary-payments.store') }}" method="POST" class="payroll-form border-t pt-4">@csrf
<input type="hidden" name="employee_id" value="{{ $s['employee']->id }}"><input type="hidden" name="target_period" value="{{ $month }}">
<div class="flex flex-wrap gap-3 items-end">
<label>支払日<input name="paid_at" type="date" required value="{{ $restore ? old('paid_at', $defaultPaidAt) : $defaultPaidAt }}" class="block border rounded p-2"></label>
<label>市県民税（円）<input name="resident_tax" type="number" min="0" required value="{{ $restore ? old('resident_tax', 0) : 0 }}" class="block border rounded p-2 w-36"></label>
<label>方法<select name="method" class="block border rounded p-2"><option @selected(!$restore || old('method', '現金') === '現金')>現金</option><option @selected($restore && old('method') === '銀行振込')>銀行振込</option></select></label>
<button class="bg-blue-600 text-white px-4 py-2 rounded">支払いを記録</button>
</div>
<p class="preview mt-4 font-bold" aria-live="polite">@if($s['tax'] !== null)源泉徴収額：¥{{ number_format($s['tax']) }} ／ 差引支給額：¥{{ number_format($s['worked_amount'] - $s['tax']) }}@else{{ $s['error'] }}@endif</p>
</form>
@endif
</div>
@empty<p>この月の勤怠・給与データはありません。在籍中の専従者は勤怠なしで表示されます。</p>@endforelse
<script>
document.querySelectorAll('.payroll-form').forEach(form => {
    let sequence = 0;
    form.addEventListener('input', async () => {
        const current = ++sequence;
        const output = form.querySelector('.preview');
        output.textContent = '計算中…';
        if (!form.checkValidity()) { output.textContent = '支払日と市県民税を確認してください。'; return; }
        const params = new URLSearchParams();
        ['employee_id', 'target_period', 'paid_at', 'resident_tax'].forEach(key => params.set(key, form.elements[key].value));
        try {
            const response = await fetch(@json(route('salary-payments.preview')) + '?' + params, {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (current !== sequence) return;
            output.textContent = response.ok ? `源泉徴収額：¥${data.tax.toLocaleString()} ／ 差引支給額：¥${data.net.toLocaleString()}` : Object.values(data.errors || {error: ['計算できませんでした。']}).flat().join(' ');
        } catch (error) { if (current === sequence) output.textContent = '計算結果を取得できませんでした。入力を変更して再試行してください。'; }
    });
});
</script>
@endsection
