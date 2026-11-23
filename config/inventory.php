<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stock ledger
    |--------------------------------------------------------------------------
    |
    | When enabled, on-hand quantity is the sum of equipment_stock_movements.
    | equipment_quantity is only a cache of that sum.
    |
    */

    'ledger_v1' => env('INVENTORY_LEDGER_V1', true),

];
