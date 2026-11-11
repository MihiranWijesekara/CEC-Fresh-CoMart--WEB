<?php
/**
 * Single source of truth for store / purchase-method settings.
 * Used by the header, checkout, checkout processing, order confirmation and admin.
 */
return [
    'name'          => 'Main Branch',
    'area'          => 'Rajagiriya',
    'label'         => 'Main Branch - Rajagiriya',
    'address'       => 'Freshcomart Main Branch, Sri Jayawardenepura Mawatha, Rajagiriya',
    'town'          => 'Rajagiriya',
    'phone'         => '+94 11 288 0000',
    'hours'         => 'Open daily 8:00 AM - 8:00 PM',
    'delivery_fee'  => 450.00, // same value the checkout used before
    'pickup_slots'  => [
        '09:00 AM - 11:00 AM',
        '11:00 AM - 01:00 PM',
        '01:00 PM - 03:00 PM',
        '03:00 PM - 05:00 PM',
        '05:00 PM - 07:00 PM',
    ],
    'pickup_days'   => 3, // today + next 2 days
];

