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

    table.book { width: 100%; border-collapse: collapse; }
    table.book th, table.book td { border: 1px solid #999; padding: 3px 6px; font-size: 9px; }
    table.book th { background: #eee; text-align: center; font-weight: bold; }
    table.book td.num { text-align: right; }
    table.book td.center { text-align: center; }
    .opening-row td, .closing-row td { font-weight: bold; background: #f5f5f5; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $type }}出納帳</h1>
    <div class="period">{{ $label }}</div>
</div>

<table class="book">
    <thead>
        <tr>
            <th style="width: 55px;">日付</th>
            <th>摘要</th>
            <th>支払い先</th>
            <th style="width: 70px;">収入</th>
            <th style="width: 70px;">支出</th>
            <th style="width: 75px;">残高</th>
        </tr>
    </thead>
    <tbody>
        <tr class="opening-row">
            <td colspan="3" class="center">前期繰越</td>
            <td class="num"></td>
            <td class="num"></td>
            <td class="num">¥{{ number_format($book['opening']) }}</td>
        </tr>
        @foreach ($book['rows'] as $row)
        <tr>
            <td class="center">{{ $row['date']->format('n/j') }}</td>
            <td>{{ $row['description'] }}</td>
            <td>{{ $row['payee'] }}</td>
            <td class="num">{{ $row['income'] ? '¥'.number_format($row['income']) : '' }}</td>
            <td class="num">{{ $row['expense'] ? '¥'.number_format($row['expense']) : '' }}</td>
            <td class="num">¥{{ number_format($row['balance']) }}</td>
        </tr>
        @endforeach
        <tr class="closing-row">
            <td colspan="3" class="center">期間合計・繰越</td>
            <td class="num">¥{{ number_format($book['income_total']) }}</td>
            <td class="num">¥{{ number_format($book['expense_total']) }}</td>
            <td class="num">{{ $type === '現金' ? '次月繰越' : '翌年繰越' }}<br>¥{{ number_format($book['closing']) }}</td>
        </tr>
    </tbody>
</table>

<div style="margin-top: 12px; page-break-inside: avoid;">
    <strong>繰越を含む収支の照合</strong>
    <p>前期繰越＋収入合計：¥{{ number_format($book['opening']) }} ＋ ¥{{ number_format($book['income_total']) }} ＝ ¥{{ number_format($book['receipt_total']) }}</p>
    <p>{{ $type === '現金' ? '次月繰越' : '翌年繰越' }}＋支出合計：¥{{ number_format($book['closing']) }} ＋ ¥{{ number_format($book['expense_total']) }} ＝ ¥{{ number_format($book['payment_total']) }}</p>
    <strong>{{ $book['balanced'] ? '一致しています' : '一致していません' }}（差額：¥{{ number_format($book['receipt_total'] - $book['payment_total']) }}）</strong>
    <p>帳簿内の計算の一致を確認しています。実際の現金・銀行残高との照合は別途必要です。</p>
</div>
</body>
</html>