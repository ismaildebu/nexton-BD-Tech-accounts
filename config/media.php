<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | System Default Free Percentage
    |--------------------------------------------------------------------------
    |
    | Last fallback in the chain:
    |   Party free_percentage -> Publication default_free_percentage -> here.
    | Used only when neither the party nor the publication has an
    | override set (i.e. both columns are NULL).
    |
    */
    'default_free_percentage' => (float) env('MEDIA_DEFAULT_FREE_PERCENTAGE', 0),

    /*
    |--------------------------------------------------------------------------
    | Default Print Planning Buffer Percentage
    |--------------------------------------------------------------------------
    |
    | Applied on top of expected_total_quantity when a Print Plan is
    | calculated without an explicit buffer being supplied.
    |
    */
    'default_buffer_percentage' => (float) env('MEDIA_DEFAULT_BUFFER_PERCENTAGE', 5),

    /*
    |--------------------------------------------------------------------------
    | Print Planning History Window
    |--------------------------------------------------------------------------
    |
    | Number of most recent MediaDistribution records (per publication,
    | before the plan date) averaged to produce average_distribution_quantity,
    | expected_paid_quantity and expected_free_quantity.
    |
    */
    'print_plan_history_count' => (int) env('MEDIA_PRINT_PLAN_HISTORY_COUNT', 7),

];
