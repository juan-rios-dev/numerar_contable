<?php

use Illuminate\Support\Facades\Route;
use Numerar\Contable\Http\Controllers\AccountClassController;
use Numerar\Contable\Http\Controllers\AccountController;
use Numerar\Contable\Http\Controllers\CostCenterController;
use Numerar\Contable\Http\Controllers\EntryController;
use Numerar\Contable\Http\Controllers\EntrySequenceController;
use Numerar\Contable\Http\Controllers\EntryTypeController;
use Numerar\Contable\Http\Controllers\FiscalYearController;
use Numerar\Contable\Http\Controllers\PeriodController;
use Numerar\Contable\Http\Controllers\ReportController;
use Numerar\Contable\Http\Controllers\TerceroController;

// Dashboard
Route::get('/', fn () => view('contable::dashboard'))->name('dashboard');

// ── Tipos de Comprobante y Numeración ─────────────────────────
Route::prefix('entry-types')->name('entry-types.')->group(function () {
    Route::get('/',                      [EntryTypeController::class, 'index'])->name('index');
    Route::post('/',                     [EntryTypeController::class, 'store'])->name('store');
    Route::put('/{entryType}',           [EntryTypeController::class, 'update'])->name('update');
    Route::patch('/{entryType}/toggle',  [EntryTypeController::class, 'toggle'])->name('toggle');
    Route::delete('/{entryType}',        [EntryTypeController::class, 'destroy'])->name('destroy');

    Route::prefix('/{entryType}/sequences')->name('sequences.')->group(function () {
        Route::get('/',                       [EntrySequenceController::class, 'index'])->name('index');
        Route::post('/',                      [EntrySequenceController::class, 'store'])->name('store');
        Route::put('/{sequence}',             [EntrySequenceController::class, 'update'])->name('update');
        Route::patch('/{sequence}/toggle',    [EntrySequenceController::class, 'toggle'])->name('toggle');
    });
});

// ── Clases Contables (solo lectura, inmutables) ───────────────
Route::prefix('account-classes')->name('account-classes.')->group(function () {
    Route::get('/', [AccountClassController::class, 'index'])->name('index');
});

// ── Catálogo de Cuentas ───────────────────────────────────────
Route::prefix('accounts')->name('accounts.')->group(function () {
    Route::get('/tree',              [AccountController::class, 'tree'])->name('tree');
    Route::get('/flat',              [AccountController::class, 'flat'])->name('flat');
    Route::get('/',                  [AccountController::class, 'index'])->name('index');
    Route::get('/create',            [AccountController::class, 'create'])->name('create');
    Route::post('/',                 [AccountController::class, 'store'])->name('store');
    Route::get('/{account}/children', [AccountController::class, 'children'])->name('children');
    Route::get('/{account}/edit',    [AccountController::class, 'edit'])->name('edit');
    Route::put('/{account}',         [AccountController::class, 'update'])->name('update');
    Route::patch('/{account}/toggle',[AccountController::class, 'toggle'])->name('toggle');
    Route::get('/{account}/delete',  [AccountController::class, 'showDelete'])->name('delete');
    Route::delete('/{account}',      [AccountController::class, 'destroy'])->name('destroy');
});

// ── Terceros ──────────────────────────────────────────────────
Route::prefix('terceros')->name('terceros.')->group(function () {
    Route::get('/',                       [TerceroController::class, 'index'])->name('index');
    Route::post('/',                      [TerceroController::class, 'store'])->name('store');
    Route::put('/{tercero}',              [TerceroController::class, 'update'])->name('update');
    Route::patch('/{tercero}/toggle',     [TerceroController::class, 'toggle'])->name('toggle');
    Route::get('/search',                 [TerceroController::class, 'search'])->name('search');
});

// ── Centros de Costo ──────────────────────────────────────────
Route::prefix('cost-centers')->name('cost-centers.')->group(function () {
    Route::get('/',                       [CostCenterController::class, 'index'])->name('index');
    Route::post('/',                      [CostCenterController::class, 'store'])->name('store');
    Route::put('/{costCenter}',           [CostCenterController::class, 'update'])->name('update');
    Route::patch('/{costCenter}/toggle',  [CostCenterController::class, 'toggle'])->name('toggle');
});

// ── Ejercicio Fiscal ──────────────────────────────────────────
Route::prefix('fiscal-years')->name('fiscal-years.')->group(function () {
    Route::get('/{year}/close',    [FiscalYearController::class, 'closeForm'])->name('close.form');
    Route::post('/{year}/close',   [FiscalYearController::class, 'close'])->name('close');
    Route::patch('/{year}/reopen', [FiscalYearController::class, 'reopen'])->name('reopen');
    Route::get('/{year}/result',   [FiscalYearController::class, 'result'])->name('result');
});

// ── Periodos Contables ────────────────────────────────────────
Route::prefix('periods')->name('periods.')->group(function () {
    Route::get('/',                  [PeriodController::class, 'index'])->name('index');
    Route::post('/',                 [PeriodController::class, 'store'])->name('store');
    Route::patch('/{period}/close',  [PeriodController::class, 'close'])->name('close');
    Route::patch('/{period}/open',   [PeriodController::class, 'open'])->name('open');
});

// ── Comprobantes ──────────────────────────────────────────────
Route::prefix('entries')->name('entries.')->group(function () {
    Route::get('/',                  [EntryController::class, 'index'])->name('index');
    Route::get('/create',            [EntryController::class, 'create'])->name('create');
    Route::post('/',                 [EntryController::class, 'store'])->name('store');
    Route::get('/{entry}',           [EntryController::class, 'show'])->name('show');
    Route::get('/{entry}/edit',      [EntryController::class, 'edit'])->name('edit');
    Route::put('/{entry}',           [EntryController::class, 'update'])->name('update');
    Route::patch('/{entry}/void',    [EntryController::class, 'void'])->name('void');
    Route::delete('/{entry}',        [EntryController::class, 'destroy'])->name('destroy');
});

// ── Reportes ──────────────────────────────────────────────────
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/journal',           [ReportController::class, 'journal'])->name('journal');
    Route::get('/general-ledger',    [ReportController::class, 'generalLedger'])->name('general-ledger');
    Route::get('/trial-balance',     [ReportController::class, 'trialBalance'])->name('trial-balance');
    Route::get('/balance-sheet',     [ReportController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/income-statement',  [ReportController::class, 'incomeStatement'])->name('income-statement');
    Route::get('/cost-center',          [ReportController::class, 'costCenter'])->name('cost-center');
    Route::get('/third-party-ledger',   [ReportController::class, 'thirdPartyLedger'])->name('third-party-ledger');
});
