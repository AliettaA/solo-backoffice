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
    html, body, table, th, td, div, span, p, h1 { font-family: 'ipaex', sans-serif; }
    body { font-size: 9px; color: #222; }
    .header { text-align: center; margin-bottom: 15px; }
    .header h1 { font-size: 18px; letter-spacing: 4px; margin: 0; }
    .header .period { font-size: 11px; margin-top: 4px; }

    table.journal { width: 100%; border-collapse: collapse; }
    table.journal th, table.journal td { border: 1px solid #999; padding: 3px 5px; font-size: 8.5px; }
    table.journal th { background: #eee; text-align: center; font-weight: bold; }
    table.journal td.num { text-align: right; }
    table.journal td.center { text-align: center; }

    .total-row td { font-weight: bold; background: #f5f5f5; border-top: 2px solid #333; }
</style>
</head>
<body>

<div class="header">
    <h1>仕訳帳</h1>
    <div class="period">対象月: {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('Y年n月') }}</div>
</div>

<table class="journal">
    <thead>
        <tr>
            <th style="width: 55px;">日付</th>
            <th style="width: 70px;">借方金額</th>
            <th style="width: 90px;">借方科目</th>
            <th>摘要</th>
            <th style="width: 90px;">貸方科目</th>
            <th style="width: 70px;">貸方金額</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($entries as $entry)
            @php
                $debits = $entry->lines->where('side', 'debit')->values();
                $credits = $entry->lines->where('side', 'credit')->values();
                $rowCount = max($debits->count(), $credits->count(), 1);
            @endphp
            @for ($i = 0; $i < $rowCount; $i++)
                @php
                    $debitLine = $debits->get($i);
                    $creditLine = $credits->get($i);

                    $memoParts = [];
                    if ($i === 0 && $entry->description) {
                        $memoParts[] = $entry->description;
                    }
                    if ($debitLine && $debitLine->counterparty_name) {
                        $memoParts[] = $debitLine->counterparty_name;
                    }
                    if ($debitLine && $debitLine->memo) {
                        $memoParts[] = $debitLine->memo;
                    }
                    if ($creditLine && $creditLine->memo) {
                        $memoParts[] = $creditLine->memo;
                    }
                    $memoText = implode(' ', $memoParts);
                @endphp
                <tr>
                    <td class="center">{{ $i === 0 ? $entry->entry_date->format('n/j') : '' }}</td>
                    <td class="num">{{ $debitLine ? '¥'.number_format($debitLine->amount) : '' }}</td>
                    <td>{{ $debitLine ? $debitLine->category->name : '' }}</td>
                    <td>{{ $memoText }}</td>
                    <td>{{ $creditLine ? $creditLine->category->name : '' }}</td>
                    <td class="num">{{ $creditLine ? '¥'.number_format($creditLine->amount) : '' }}</td>
                </tr>
            @endfor
        @endforeach

        <tr class="total-row">
            <td class="center">合計</td>
            <td class="num">¥{{ number_format($debitTotal) }}</td>
            <td colspan="2"></td>
            <td></td>
            <td class="num">¥{{ number_format($creditTotal) }}</td>
        </tr>
    </tbody>
</table>

</body>
</html>