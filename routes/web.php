<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CashBookController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LumberInvoiceController;
use App\Http\Controllers\LumberInvoicePaymentController;
use App\Http\Controllers\MonthlyInvoiceController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\PayeeController;
use App\Http\Controllers\PricingMasterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalaryPaymentController;
use Illuminate\Support\Facades\Route;

Route::resource('employees', EmployeeController::class);

Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances.index');
Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
Route::put('attendances/{attendance}', [AttendanceController::class, 'update'])->name('attendances.update');
Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy'])->name('attendances.destroy');

Route::get('salary-payments/preview', [SalaryPaymentController::class, 'preview'])->name('salary-payments.preview');
Route::get('salary-payments', [SalaryPaymentController::class, 'index'])->name('salary-payments.index');
Route::post('salary-payments', [SalaryPaymentController::class, 'store'])->name('salary-payments.store');
Route::delete('salary-payments/{salaryPayment}', [SalaryPaymentController::class, 'destroy'])->name('salary-payments.destroy');

Route::get('opening-balances', [OpeningBalanceController::class, 'index'])->name('opening-balances.index');
Route::post('opening-balances', [OpeningBalanceController::class, 'store'])->name('opening-balances.store');
Route::delete('opening-balances/{openingBalance}', [OpeningBalanceController::class, 'destroy'])->name('opening-balances.destroy');

Route::get('cash-book', [CashBookController::class, 'index'])->name('cash-book.index');
Route::get('cash-book/pdf', [CashBookController::class, 'pdf'])->name('cash-book.pdf');

Route::get('general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');
Route::get('general-ledger/pdf', [GeneralLedgerController::class, 'pdf'])->name('general-ledger.pdf');

Route::resource('journal-entries', JournalEntryController::class);
Route::get('journal-entries-pdf', [JournalEntryController::class, 'pdf'])->name('journal-entries.pdf');

Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

Route::get('payees', [PayeeController::class, 'index'])->name('payees.index');
Route::post('payees', [PayeeController::class, 'store'])->name('payees.store');
Route::put('payees/{payee}', [PayeeController::class, 'update'])->name('payees.update');
Route::delete('payees/{payee}', [PayeeController::class, 'destroy'])->name('payees.destroy');

Route::get('/', function () {
    return redirect()->route('clients.index');
});

Route::resource('clients', ClientController::class);
Route::resource('products', ProductController::class);
Route::resource('lumber-invoices', LumberInvoiceController::class);
Route::get('lumber-invoices/{lumber_invoice}/pdf', [LumberInvoiceController::class, 'pdf'])->name('lumber-invoices.pdf');

Route::get('pricing-master', [PricingMasterController::class, 'index'])->name('pricing-master.index');

// 樹種
Route::post('pricing-master/species', [PricingMasterController::class, 'storeSpecies'])->name('pricing-master.species.store');
Route::get('pricing-master/species/{species}/edit', [PricingMasterController::class, 'editSpecies'])->name('pricing-master.species.edit');
Route::put('pricing-master/species/{species}', [PricingMasterController::class, 'updateSpecies'])->name('pricing-master.species.update');
Route::delete('pricing-master/species/{species}', [PricingMasterController::class, 'destroySpecies'])->name('pricing-master.species.destroy');

// 幅ごとの基本単価
Route::post('pricing-master/species/{species}/width-prices', [PricingMasterController::class, 'storeWidthPrice'])->name('pricing-master.width-prices.store');
Route::delete('pricing-master/width-prices/{widthPrice}', [PricingMasterController::class, 'destroyWidthPrice'])->name('pricing-master.width-prices.destroy');

// 厚み加算
Route::post('pricing-master/thickness-surcharges', [PricingMasterController::class, 'storeThicknessSurcharge'])->name('pricing-master.thickness-surcharges.store');
Route::delete('pricing-master/thickness-surcharges/{thicknessSurcharge}', [PricingMasterController::class, 'destroyThicknessSurcharge'])->name('pricing-master.thickness-surcharges.destroy');

// 数量帯加算
Route::post('pricing-master/quantity-tiers', [PricingMasterController::class, 'storeQuantityTier'])->name('pricing-master.quantity-tiers.store');
Route::put('pricing-master/quantity-tiers/{quantityTier}', [PricingMasterController::class, 'updateQuantityTier'])->name('pricing-master.quantity-tiers.update');
Route::delete('pricing-master/quantity-tiers/{quantityTier}', [PricingMasterController::class, 'destroyQuantityTier'])->name('pricing-master.quantity-tiers.destroy');

// 長さ調整などの設定値
Route::post('pricing-master/settings', [PricingMasterController::class, 'updateSettings'])->name('pricing-master.settings.update');

Route::get('company', [CompanyController::class, 'edit'])->name('company.edit');
Route::put('company', [CompanyController::class, 'update'])->name('company.update');

Route::post('lumber-invoices/{lumber_invoice}/payments', [LumberInvoicePaymentController::class, 'store'])->name('lumber-invoices.payments.store');
Route::delete('lumber-invoices/payments/{payment}', [LumberInvoicePaymentController::class, 'destroy'])->name('lumber-invoices.payments.destroy');

Route::get('payments', [ClientPaymentController::class, 'index'])->name('payments.index');
Route::post('payments', [ClientPaymentController::class, 'store'])->name('payments.store');
Route::delete('payments/{payment}', [ClientPaymentController::class, 'destroy'])->name('payments.destroy');

Route::get('monthly-invoices', [MonthlyInvoiceController::class, 'index'])->name('monthly-invoices.index');
Route::get('monthly-invoices/pdf', [MonthlyInvoiceController::class, 'pdf'])->name('monthly-invoices.pdf');
Route::get('financial-statements', [FinancialStatementController::class, 'index'])->name('financial-statements.index');
Route::put('financial-statements/categories/{category}', [FinancialStatementController::class, 'classify'])->name('financial-statements.classify');

Route::get('financial-statements/pdf', [FinancialStatementController::class, 'pdf'])->name('financial-statements.pdf');

Route::get('attendances/pdf', [AttendanceController::class, 'pdf'])->name('attendances.pdf');
