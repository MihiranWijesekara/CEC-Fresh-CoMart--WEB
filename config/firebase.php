<?php
/**
 * Freshcomart (CECWeb) — Unified Firebase Configuration (100% SQL-Free)
 * All database operations run exclusively on Firebase Authentication + Cloud Firestore
 * via assets/js/firebase-config.js and assets/js/firebase-service.js.
 */

return [
    'firebase' => [
        'apiKey'            => 'AIzaSyC9NzyCr2MoDMmcDUqplBou0FNT2Dxx5Ds',
        'authDomain'        => 'fresh-co-mart.firebaseapp.com',
        'projectId'         => 'fresh-co-mart',
        'storageBucket'     => 'fresh-co-mart.firebasestorage.app',
        'messagingSenderId' => '269666462088',
        'appId'             => '1:269666462088:web:3e62ca93cc95335f31f23d',
    ],
    'store' => [
        'name'         => 'Main Branch',
        'area'         => 'Rajagiriya',
        'label'        => 'Main Branch - Rajagiriya',
        'address'      => 'Freshcomart Main Branch, Sri Jayawardenepura Mawatha, Rajagiriya',
        'town'         => 'Rajagiriya',
        'phone'        => '+94 11 288 0000',
        'hours'        => 'Open daily 8:00 AM - 9:00 PM',
        'delivery_fee' => 450.00,
        'pickup_days'  => 7,
        'pickup_slots' => [
            '09:00 AM - 11:00 AM',
            '11:00 AM - 01:00 PM',
            '01:00 PM - 03:00 PM',
            '03:00 PM - 05:00 PM',
            '05:00 PM - 07:00 PM',
            '07:00 PM - 09:00 PM',
        ],
    ],
];

