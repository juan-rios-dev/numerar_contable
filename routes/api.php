<?php

use Illuminate\Support\Facades\Route;
use Numerar\Contable\Http\Controllers\Api\AccountClassApiController;
use Numerar\Contable\Http\Controllers\Api\AccountApiController;
use Numerar\Contable\Http\Controllers\Api\CostCenterApiController;
use Numerar\Contable\Http\Controllers\Api\EntryApiController;
use Numerar\Contable\Http\Controllers\Api\EntryTypeApiController;
use Numerar\Contable\Http\Controllers\Api\FiscalYearApiController;
use Numerar\Contable\Http\Controllers\Api\PeriodApiController;
use Numerar\Contable\Http\Controllers\Api\ReportApiController;
use Numerar\Contable\Http\Controllers\Api\TerceroApiController;

// ── Clases contables (solo lectura) ──────────────────────────
Route::get('account-classes', [AccountClassApiController::class, 'index'])->name('account-classes.index');

// ── Catálogo de cuentas ───────────────────────────────────────
Route::prefix('accounts')->name('accounts.')->group(function () {
    Route::get('/flat',             [AccountApiController::class, 'flat'])->name('flat');
    Route::get('/tree',             [AccountApiController::class, 'tree'])->name('tree');
    Route::get('/',                 [AccountApiController::class, 'index'])->name('index');
    Route::post('/',                [AccountApiController::class, 'store'])->name('store');
    Route::get('/{account}',        [AccountApiController::class, 'show'])->name('show');
    Route::put('/{account}',        [AccountApiController::class, 'update'])->name('update');
    Route::patch('/{account}/toggle', [AccountApiController::class, 'toggle'])->name('toggle');
    Route::delete('/{account}',     [AccountApiController::class, 'destroy'])->name('destroy');
});

// ── Terceros ──────────────────────────────────────────────────
Route::apiResource('terceros', TerceroApiController::class);
Route::patch('terceros/{tercero}/toggle', [TerceroApiController::class, 'toggle'])->name('terceros.toggle');
Route::get('terceros-search', [TerceroApiController::class, 'search'])->name('terceros.search');

// ── Centros de costo ──────────────────────────────────────────
Route::apiResource('cost-centers', CostCenterApiController::class);
Route::patch('cost-centers/{costCenter}/toggle', [CostCenterApiController::class, 'toggle'])->name('cost-centers.toggle');

// ── Tipos de comprobante ──────────────────────────────────────
Route::apiResource('entry-types', EntryTypeApiController::class);
Route::patch('entry-types/{entryType}/toggle', [EntryTypeApiController::class, 'toggle'])->name('entry-types.toggle');

// ── Periodos contables ────────────────────────────────────────
Route::prefix('periods')->name('periods.')->group(function () {
    Route::get('/',                 [PeriodApiController::class, 'index'])->name('index');
    Route::post('/',                [PeriodApiController::class, 'store'])->name('store');
    Route::get('/{period}',         [PeriodApiController::class, 'show'])->name('show');
    Route::patch('/{period}/close', [PeriodApiController::class, 'close'])->name('close');
    Route::patch('/{period}/open',  [PeriodApiController::class, 'open'])->name('open');
});

// ── Ejercicio fiscal ──────────────────────────────────────────
Route::prefix('fiscal-years')->name('fiscal-years.')->group(function () {
    Route::get('/',                  [FiscalYearApiController::class, 'index'])->name('index');
    Route::post('/{year}/close',     [FiscalYearApiController::class, 'close'])->name('close');
    Route::patch('/{year}/reopen',   [FiscalYearApiController::class, 'reopen'])->name('reopen');
    Route::get('/{year}/result',     [FiscalYearApiController::class, 'result'])->name('result');
});

// ── Comprobantes ──────────────────────────────────────────────
Route::prefix('entries')->name('entries.')->group(function () {
    Route::get('/',                 [EntryApiController::class, 'index'])->name('index');
    Route::post('/',                [EntryApiController::class, 'store'])->name('store');
    Route::get('/{entry}',          [EntryApiController::class, 'show'])->name('show');
    Route::put('/{entry}',          [EntryApiController::class, 'update'])->name('update');
    Route::patch('/{entry}/void',   [EntryApiController::class, 'void'])->name('void');
    Route::delete('/{entry}',       [EntryApiController::class, 'destroy'])->name('destroy');
});

// ── Reportes ──────────────────────────────────────────────────
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/journal',          [ReportApiController::class, 'journal'])->name('journal');
    Route::get('/general-ledger',   [ReportApiController::class, 'generalLedger'])->name('general-ledger');
    Route::get('/trial-balance',    [ReportApiController::class, 'trialBalance'])->name('trial-balance');
    Route::get('/balance-sheet',    [ReportApiController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/income-statement', [ReportApiController::class, 'incomeStatement'])->name('income-statement');
    Route::get('/cost-center',      [ReportApiController::class, 'costCenter'])->name('cost-center');
});
