<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Extra payment notification recipients
    |--------------------------------------------------------------------------
    |
    | New customer payments that need verification are mailed to the admins of
    | the paying company. Addresses listed here (comma separated) are added to
    | that list, for example a shared accounts mailbox.
    |
    */

    'admin_notification_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PAYMENT_ADMIN_NOTIFICATION_EMAILS', ''))
    ))),

];
