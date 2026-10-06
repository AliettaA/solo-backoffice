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
    html, body, table, th, td, div, span, p, h1, h2 { font-family: 'ipaex', sans-serif; }
    body { font-size: 9px; color: #222; }
    .header { text-align: center; margin-bottom: 15px; }
    .header h1 { font-size: 18px; letter-spacing: 4px; margin: 0; }
    .header .period { font-size: 11px; margin-top: 4px; }

    .category-page { margin-bottom: 22px; }
    .category-compact { page-break-inside: avoid; }
    .category-title, h3 { page-break-after: avoid; }
    table.ledger thead { display: table-header-group; }
    table.ledger tr { page-break-inside: avoid; }
    .category-title { font-size: 13px; font-weight: bold; background: #333; color: #fff; padding: 5px 10px; margin-bottom: 8px; }

    table.ledger { width: 100%; border-collapse: collapse; }
    table.ledger th, table.ledger td { border: 1px solid #999; padding: 3px 6px; font-size: 9px; }
    table.ledger th { background: #eee; text-align: center; font-weight: bold; }
    table.ledger td.num { text-align: right; }
    table.ledger td.center { text-align: center; }
    .opening-row td, .closing-row td { font-weight: bold; background: #f5f5f5; }
</style>
</head>
<body>

<div class="header">
    <h1>総勘定元帳</h1>
    <div class="period">{{ $year }}年</div>
</div>

@foreach ($ledgers as $ledger)
<div class="category-page {{ !isset($ledger['deposit_ledgers']) && !$ledger['withholding_sections'] && count($ledger['rows']) <= 20 ? 'category-compact' : '' }}">
    <div class="category-title">{{ isset($ledger['deposit_ledgers']) ? '預り金・市県民税預り金' : $ledger['category']->name }}</div>

    @if(isset($ledger['deposit_ledgers']))
        @include('general_ledger.deposit_ledgers')
    @elseif($ledger['withholding_sections'])
        @include('general_ledger.withholding_sections')
    @elseif($ledger['payable_sections'])
        @include('general_ledger.payable_sections')
    @else
    <table class="ledger">
        <thead>
            <tr>
                <th style="width: 55px;">日付</th>
                <th>項目</th>
                <th>支払い先</th>
                <th style="width: 80px;">金額</th>
                <th style="width: 80px;">合計</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening-row">
                <td colspan="3" class="center">前期繰越</td>
                <td class="num"></td>
                <td class="num">¥{{ number_format($ledger['opening']) }}</td>
            </tr>
            @foreach ($ledger['rows'] as $row)
            <tr>
                <td class="center">{{ $row['date']->format('n/j') }}</td>
                <td>{{ $row['item'] }}</td>
                <td>{{ $row['payee'] }}</td>
                <td class="num">¥{{ number_format($row['amount']) }}</td>
                <td class="num">¥{{ number_format($row['balance']) }}</td>
            </tr>
            @endforeach
            <tr class="closing-row">
                <td colspan="3" class="center">期末残高</td>
                <td class="num"></td>
                <td class="num">¥{{ number_format($ledger['closing']) }}</td>
            </tr>
        </tbody>
    </table>
    @endif
</div>
@endforeach

</body>
</html>