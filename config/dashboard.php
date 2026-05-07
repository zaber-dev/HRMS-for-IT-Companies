<?php

return [

    /*
    |--------------------------------------------------------------------------
    | High PTO Threshold
    |--------------------------------------------------------------------------
    |
    | The number of approved or pending PTO leave days in the current calendar
    | year at or above which an employee is flagged as a PTO alert on the
    | HR dashboard.
    |
    */

    'high_pto_threshold' => env('DASHBOARD_HIGH_PTO_THRESHOLD', 14),

];
