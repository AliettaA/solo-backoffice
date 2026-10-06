<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
   @font-face {
    font-family: 'ipaex';
        src: url('{{ resource_path('fonts/ipaexg.ttf') }}') format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    @font-face {
        font-family: 'ipaex';
        src: url('{{ resource_path('fonts/ipaexg.ttf') }}') format('truetype');
        font-weight: bold;
        font-style: normal;
    }
    html, body, table, th, td, div, span, p, h1 {
        font-family: 'ipaex', sans-serif;
    }
    body { font-size: 10px; color: #222; }
    table.items th, table.items td {
        font-family: 'ipaex', sans-serif;
    }
    table.items th { background: #eee; text-align: center; font-family: 'ipaex', sans-serif; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h1 { font-size: 20px; letter-spacing: 4px; margin: 0; font-family: 'ipaex', sans-serif; }
    .meta { display: table; width: 100%; margin-bottom: 20px; }
    .meta-left { display: table-cell; width: 50%; vertical-align: top; }
    .meta-right { display: table-cell; width: 50%; vertical-align: top; text-align: left; }
    .client-name { font-size: 14px; border-bottom: 2px solid #333; padding-bottom: 4px; margin-bottom: 8px; }
    .total-box { background: #f0f0f0; padding: 10px; margin-top: 8px; }
    .total-box .amount { font-size: 18px; font-weight: bold; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.items th, table.items td { border: 1px solid #999; padding: 4px 6px; font-size: 9px; }
    table.items th { background: #eee; text-align: center; font-family: 'ipaex', sans-serif; font-weight: bold; }
    table.items td.num { text-align: right; }
    table.items td.center { text-align: center; }
    .summary { width: 260px; margin-left: auto; margin-top: 15px; }
    .summary table { width: 100%; border-collapse: collapse; }
    .summary td { padding: 4px 8px; font-size: 10px; }
    .summary td.label { text-align: left; }
    .summary td.value { text-align: right; }
    .summary tr.grand td { font-weight: bold; font-size: 13px; border-top: 2px solid #333; }
    .bank { margin-top: 30px; font-size: 9px; }
    .footer-note { margin-top: 20px; font-size: 8px; color: #666; }
</style>
</head>
<body>

<div class="header">
    <h1>納品書</h1>
</div>

<div class="meta">
    <div class="meta-left">
        <div class="client-name">{{ $invoice->client->name }} {{ $invoice->client->honorific }}</div>
        @if ($invoice->client->address)
            <div>{{ $invoice->client->postal_code }}</div>
            <div>{{ $invoice->client->address }}</div>
        @endif
    </div>
    <div class="meta-right">
        <table style="width:65%; font-size: 10px; margin-left: auto;">
            <tr><td>納品書番号</td><td>{{ $invoice->invoice_number }}</td></tr>
            <tr><td>発行日</td><td>{{ $invoice->issue_date->format('Y年n月j日') }}</td></tr>
            @if ($company && $company->invoice_registration_number)
            <tr><td>登録番号</td><td>{{ $company->invoice_registration_number }}</td></tr>
            @endif
        </table>
        @if ($company)
        <div style="margin-top: 10px; width: 65%; margin-left: auto;">
            <div style="font-weight: bold;">{{ $company->name }}</div>
            @if ($company->address)<div>{{ $company->postal_code }} {{ $company->address }}</div>@endif
            @if ($company->phone)<div>TEL: {{ $company->phone }}</div>@endif
        </div>
        @endif
    </div>
</div>

<div class="total-box">
    金額(参考): <span class="amount">¥{{ number_format($invoice->total) }}</span>(税込)
</div>

<table class="items">
    <thead>
        <tr>
            <th>樹種</th>
            <th>サイズ(厚-幅-長)</th>
            @foreach ($invoice->lines->first()->tiers ?? [] as $t)@endforeach
            <th>1-9枚</th>
            <th>10-14枚</th>
            <th>15-19枚</th>
            <th>20枚-</th><th>戻し</th>
            <th>調整額</th>
            <th>合計枚数</th>
            <th>金額</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->lines as $line)
        @php
            $tierMap = $line->tiers->keyBy('tier_label');
            $invoiceAdjustment = $loop->last ? (int) $invoice->invoice_adjustment_amount : 0;
        @endphp
        <tr>
            <td class="center">{{ $line->species->name }}</td>
            <td class="center">{{ rtrim(rtrim(number_format($line->thickness, 1), '0'), '.') }}-{{ rtrim(rtrim(number_format($line->width, 1), '0'), '.') }}-{{ rtrim(rtrim(number_format($line->length, 1), '0'), '.') }}</td>
            @foreach (['1-9', '10-14', '15-19', '20-'] as $label)
                @php $t = $tierMap->get($label); @endphp
                <td class="num">
                    @if ($t && $t->quantity > 0)
                        {{ $t->quantity }}枚<br><small>@ ¥{{ number_format($t->unit_price) }}</small>
                    @else
                        -
                    @endif
                </td>
            @endforeach
            <td class="num">{{ $line->returned_quantity ? number_format($line->returned_quantity).'枚' : '-' }}</td>
            <td class="num">
                @if($line->adjustment_amount != 0){{ number_format($line->adjustment_amount) }}円@endif
                @if($invoiceAdjustment != 0)
                    @if($line->adjustment_amount != 0)<br>@endif
                    {{ $invoice->invoice_adjustment_description }}<br>{{ number_format($invoiceAdjustment) }}円
                @elseif($line->adjustment_amount == 0)-@endif
            </td>
            <td class="num">{{ number_format($line->total_quantity) }}枚</td>
            <td class="num">¥{{ number_format($line->line_total + $invoiceAdjustment) }}</td>
        </tr>
        @endforeach
        @if($invoice->lines->isEmpty() && $invoice->invoice_adjustment_amount != 0)
        <tr><td colspan="7"></td><td class="center" style="overflow-wrap: break-word;">{{ $invoice->invoice_adjustment_description }}<br>{{ number_format($invoice->invoice_adjustment_amount) }}円</td><td></td><td></td></tr>
        @endif
    </tbody>
</table>

<div class="summary">
    <table>
        <tr><td class="label">小計</td><td class="value">¥{{ number_format($invoice->subtotal) }}</td></tr>
        <tr><td class="label">消費税(10%)</td><td class="value">¥{{ number_format($invoice->tax_amount) }}</td></tr>
        <tr class="grand"><td class="label">合計金額(参考)</td><td class="value">¥{{ number_format($invoice->total) }}</td></tr>
    </table>
</div>

@if ($invoice->notes)
<div class="footer-note">{{ $invoice->notes }}</div>
@endif

</body>
</html>