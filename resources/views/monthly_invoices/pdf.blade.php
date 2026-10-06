<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 44px 36px 28px; }
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
    html, body, table, th, td, div, span, p, h1 { font-family: 'ipaex', sans-serif; }
    body { font-size: 11px; color: #222; }
    .header { text-align: center; margin-bottom: 12px; }
    .header h1 { font-size: 20px; letter-spacing: 4px; margin: 0; }
    .meta { display: table; width: 100%; margin-bottom: 12px; }
    .meta-left { display: table-cell; width: 50%; vertical-align: top; }
    .meta-right { display: table-cell; width: 50%; vertical-align: top; }
    .client-name { font-size: 14px; border-bottom: 2px solid #333; padding-bottom: 4px; margin-bottom: 8px; }
    .total-box { background: #f0f0f0; padding: 10px; margin: 15px auto 8px auto; width: 60%; text-align: center; }
    .total-box .amount { font-size: 18px; font-weight: bold; }

    table.items tbody { page-break-inside: avoid; }
    table.items thead { display: table-header-group; }
    table.items td.date { white-space: nowrap; vertical-align: middle; }
    .summary { page-break-inside: avoid; }
    .day-title { background: #333; color: #fff; padding: 3px 8px; font-size: 11px; font-weight: bold; }

    table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.items th, table.items td { border: 1px solid #999; padding: 2px 3px; font-size: 11px; line-height: 1.1; }
    table.items small { font-size: 11px; }
    table.items th { background: #eee; text-align: center; font-weight: bold; }
    table.items td.num { text-align: right; }
    table.items td.center { text-align: center; }

    .day-total { text-align: right; font-size: 11px; font-weight: bold; padding: 3px 4px; }

    .summary { width: 260px; margin-left: auto; margin-top: 8px; }
    .summary table { width: 100%; border-collapse: collapse; }
    .summary td { padding: 3px 8px; font-size: 11px; }
    .summary td.label { text-align: left; }
    .summary td.value { text-align: right; }
    .summary tr.grand td { font-weight: bold; font-size: 13px; border-top: 2px solid #333; }
</style>
</head>
<body>

<div class="header">
    <h1>請求書</h1>
</div>

<div class="meta">
    <div class="meta-left">
        <div class="client-name">{{ $client->name }} {{ $client->honorific }}</div>
        @if ($client->address)
            <div>{{ $client->postal_code }}</div>
            <div>{{ $client->address }}</div>
        @endif
    </div>
    <div class="meta-right">
        <table style="width:100%; font-size: 11px; margin-left: auto;">
            <tr><td>請求対象期間</td><td>{{ \Carbon\Carbon::parse($start)->format('Y/m/d') }}〜{{ \Carbon\Carbon::parse($end)->format('Y/m/d') }}</td></tr>
            <tr><td>発行日</td><td>{{ now()->format('Y年n月j日') }}</td></tr>
            @if ($company && $company->invoice_registration_number)
            <tr><td>登録番号</td><td>{{ $company->invoice_registration_number }}</td></tr>
            @endif
        </table>
        @if ($company)
        <div style="margin-top: 10px; width: 100%; margin-left: auto;">
            <div style="font-weight: bold;">{{ $company->name }}</div>
            @if ($company->address)<div>{{ $company->postal_code }} {{ $company->address }}</div>@endif
            @if ($company->phone)<div>TEL: {{ $company->phone }}</div>@endif
        </div>
        @endif
    </div>
</div>

<div class="total-box">
    ご請求金額: <span class="amount">¥{{ number_format($summary['total']) }}</span>(税込)
</div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 35px;">日付</th>
                <th>樹種/品名</th>
                <th>サイズ</th>
                <th>1-9枚</th>
                <th>10-14枚</th>
                <th>15-19枚</th>
                <th>20枚-</th><th>戻し</th>
                <th>調整額</th>
                <th>合計枚数</th>
                <th>金額</th>
                <th style="width: 60px;">納品合計<br>（税込）</th>
            </tr>
        </thead>
        @foreach ($invoices as $invoice)
        <tbody>
            @php
                $totalRows = $invoice->lines->count() + $invoice->productLines->count() + ($invoice->invoice_adjustment_amount != 0 ? 1 : 0);
                $rowNum = 0;
            @endphp

            @foreach ($invoice->lines as $line)
            @php $tierMap = $line->tiers->keyBy('tier_label'); $rowNum++; @endphp
            <tr>
                @if ($rowNum === 1)<td class="center date" rowspan="{{ $totalRows }}">{{ $invoice->issue_date->format('n/j') }}</td>@endif
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
                <td class="num">{{ $line->adjustment_amount != 0 ? number_format($line->adjustment_amount) : '-' }}</td>
                <td class="num">{{ number_format($line->total_quantity) }}枚</td>
                <td class="num">¥{{ number_format($line->line_total) }}</td>
                @if ($rowNum === 1)
                <td class="num" style="font-weight:bold; vertical-align: middle;" rowspan="{{ $totalRows }}">¥{{ number_format($invoice->total) }}</td>
                @endif
            </tr>
            @endforeach

            @foreach ($invoice->productLines as $p)
            @php $rowNum++; @endphp
            <tr>
                @if ($rowNum === 1)<td class="center date" rowspan="{{ $totalRows }}">{{ $invoice->issue_date->format('n/j') }}</td>@endif
                <td colspan="2">{{ $p->description }}</td>
                <td colspan="5" class="num">{{ rtrim(rtrim(number_format($p->quantity, 2), '0'), '.') }}個 @ ¥{{ number_format($p->unit_price) }}(税{{ $p->tax_rate }}%)</td>
                <td class="num">-</td>
                <td class="num">-</td>
                <td class="num">¥{{ number_format($p->amount) }}</td>
                @if ($rowNum === 1)
                <td class="num" style="font-weight:bold; vertical-align: middle;" rowspan="{{ $totalRows }}">¥{{ number_format($invoice->total) }}</td>
                @endif
            </tr>
            @endforeach
            @if($invoice->invoice_adjustment_amount != 0)
            @php $rowNum++; @endphp
            <tr>
                @if($rowNum === 1)<td class="center date" rowspan="{{ $totalRows }}">{{ $invoice->issue_date->format('n/j') }}</td>@endif
                <td colspan="7"></td>
                <td class="center" style="overflow-wrap: break-word;">{{ $invoice->invoice_adjustment_description }}<br>{{ number_format($invoice->invoice_adjustment_amount) }}円</td>
                <td></td><td></td>
                @if($rowNum === 1)<td class="num" rowspan="{{ $totalRows }}">¥{{ number_format($invoice->total) }}</td>@endif
            </tr>
            @endif

        </tbody>
        @endforeach
    </table>

<div class="summary">
    <table>
        <tr><td class="label">小計(10%対象)</td><td class="value">¥{{ number_format($summary['subtotal_10']) }}</td></tr>
        <tr><td class="label">消費税(10%)</td><td class="value">¥{{ number_format($summary['tax_10']) }}</td></tr>
        @if ($summary['subtotal_8'] > 0)
        <tr><td class="label">小計(8%対象)</td><td class="value">¥{{ number_format($summary['subtotal_8']) }}</td></tr>
        <tr><td class="label">消費税(8%)</td><td class="value">¥{{ number_format($summary['tax_8']) }}</td></tr>
        @endif
        <tr class="grand"><td class="label">合計金額</td><td class="value">¥{{ number_format($summary['total']) }}</td></tr>
    </table>
</div>

</body>
</html>