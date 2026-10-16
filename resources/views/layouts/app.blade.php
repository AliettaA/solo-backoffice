<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ asset('css/mobile.css') }}?v=20261016">
    <title>@yield('title', '業務管理')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="mobile-shortcuts">
        <a href="{{ route('attendances.index') }}">勤怠入力</a>
        <a href="{{ route('lumber-invoices.create') }}">納品書入力</a>
        <button type="button" aria-expanded="false" aria-controls="main-navigation" onclick="const nav = document.getElementById('main-navigation'); const open = this.getAttribute('aria-expanded') !== 'true'; this.setAttribute('aria-expanded', String(open)); nav.classList.toggle('mobile-open', open);">メニュー</button>
    </div>
    <nav id="main-navigation" class="bg-white border-b px-6 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm [&>a]:whitespace-nowrap">
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
    <main class="max-w-5xl mx-auto p-3 sm:p-6">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>