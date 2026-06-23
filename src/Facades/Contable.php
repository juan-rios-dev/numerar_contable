<?php

namespace Numerar\Contable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Numerar\Contable\Models\AccountingEntry         createEntry(array $data)
 * @method static \Numerar\Contable\Models\AccountingEntry         updateEntry(int|\Numerar\Contable\Models\AccountingEntry $entry, array $data)
 * @method static \Numerar\Contable\Models\AccountingEntry         voidEntry(int|\Numerar\Contable\Models\AccountingEntry $entry)
 * @method static bool                                              deleteEntry(int|\Numerar\Contable\Models\AccountingEntry $entry)
 * @method static \Numerar\Contable\Models\AccountingPeriod        createPeriod(array $data)
 * @method static \Numerar\Contable\Models\AccountingPeriod        closePeriod(int|\Numerar\Contable\Models\AccountingPeriod $period)
 * @method static \Numerar\Contable\Models\AccountingPeriod        openPeriod(int|\Numerar\Contable\Models\AccountingPeriod $period)
 * @method static \Numerar\Contable\Models\Account                 createAccount(array $data)
 * @method static \Numerar\Contable\Models\Account                 updateAccount(int|\Numerar\Contable\Models\Account $account, array $data)
 * @method static \Numerar\Contable\Models\Account                 toggleAccount(int|\Numerar\Contable\Models\Account $account)
 * @method static \Illuminate\Database\Eloquent\Collection         accountTree(?int $classId = null)
 * @method static array                                             accountFlat(?int $classId = null, bool $onlyMovement = false)
 * @method static \Numerar\Contable\Models\AccountClass            createAccountClass(array $data)
 * @method static \Numerar\Contable\Models\CostCenter              createCostCenter(array $data)
 * @method static \Numerar\Contable\Models\CostCenter              updateCostCenter(int|\Numerar\Contable\Models\CostCenter $costCenter, array $data)
 * @method static array                                             trialBalance(array $filters)
 * @method static array                                             generalLedger(array $filters)
 * @method static array                                             balanceSheet(array $filters)
 * @method static array                                             incomeStatement(array $filters)
 * @method static array                                             journal(array $filters)
 * @method static array                                             costCenterReport(array $filters)
 * @method static array                                             thirdPartyLedger(array $filters)
 * @method static void                                              resolveTenantUsing(\Closure $callback)
 * @method static int|string|null                                   resolveTenant()
 *
 * @see \Numerar\Contable\Services\AccountingService
 */
class Contable extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'contable';
    }
}
