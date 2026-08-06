<?php

namespace App\Support;

class RoleNames
{
    public const ADMIN = 'Admin';

    public const BRANCH_MANAGER = 'Branch Manager';

    public const ACCOUNTANT = 'Accountant';

    public const WAREHOUSE_KEEPER = 'Warehouse Keeper';

    public const SALES_REP = 'Sales Representative';

    public const SALES_MANAGER = 'Sales Manager';

    public const SALES_SUPERVISOR = 'Sales Supervisor';

    public const SALES_MAN = 'SalesMan';

    public const DISTRIBUTION_MANAGER = 'Distribution Manager';

    public const CASHIER = 'Cashier';

    public const HR_MANAGER = 'HR Manager';

    /**
     * All defined role names.
     */
    public static function all(): array
    {
        return [
            self::ADMIN,
            self::BRANCH_MANAGER,
            self::ACCOUNTANT,
            self::WAREHOUSE_KEEPER,
            self::SALES_REP,
            self::SALES_MANAGER,
            self::SALES_SUPERVISOR,
            self::SALES_MAN,
            self::DISTRIBUTION_MANAGER,
            self::CASHIER,
            self::HR_MANAGER,
        ];
    }
}
