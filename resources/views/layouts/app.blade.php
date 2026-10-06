<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', '業務管理')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <nav class="bg-white border-b px-6 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm [&>a]:whitespace-nowrap">
    <a href="{{ route('clients.index') }}" class="font-bold">業務管理システム</a>
    <a href="{{ route('lumber-invoices.index') }}" class="hover:text-blue-600">納品書</a>
    <a href="{{ route('monthly-invoices.index') }}" class="hover:text-blue-600">月次請求書</a>
    <a href="{{ route('clients.index') }}" class="hover:text-blue-600">取引先</a>
    <a href="{{ route('products.index') }}" class="hover:text-blue-600">品目</a>
    <a href="{{ route('pricing-master.index') }}" class="hover:text-blue-600">単価マスタ</a>
    <a href="{{ route('company.edit') }}" class="hover:text-blue-600">事業者情報</a>
    <a href="{{ route('payments.index') }}" class="hover:text-blue-600">入金管理</a>
    <a href="{{ route('journal-entries.index') }}" class="hover:text-blue-600">振替伝票</a>
    <a href="{{ route('expense-categories.index') }}" class="hover:text-blue-600">勘定科目</a>
    <a href="{{ route('payees.index') }}" class="hover:text-blue-600">支払先</a>
    <a href="{{ route('financial-statements.index') }}" class="hover:text-blue-600">決算書</a>
    <a href="{{ route('general-ledger.index') }}" class="hover:text-blue-600">総勘定元帳</a>
    <a href="{{ route('cash-book.index') }}" class="hover:text-blue-600">現金・預金出納帳</a>
    <a href="{{ route('opening-balances.index') }}" class="hover:text-blue-600">期首残高</a>
    <a href="{{ route('employees.index') }}" class="hover:text-blue-600">従業員</a>
    <a href="{{ route('attendances.index') }}" class="hover:text-blue-600">勤怠</a>
    <a href="{{ route('salary-payments.index') }}" class="hover:text-blue-600">給与支払い</a>
</nav>
    <main class="max-w-5xl mx-auto p-6">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>