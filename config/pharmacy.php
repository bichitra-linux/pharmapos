<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pharmacy POS Configuration
    |--------------------------------------------------------------------------
    */

    'currency' => env('PHARMACY_CURRENCY', 'NPR'),
    'currency_symbol' => 'Rs.',
    'vat_rate' => (float) env('PHARMACY_VAT_RATE', 13.0),
    'timezone' => env('APP_TIMEZONE', 'Asia/Kathmandu'),
    'date_format' => 'YYYY-MM-DD',
    'time_format' => 'HH:mm',

    /*
    |--------------------------------------------------------------------------
    | Prescription Schedule Types (Narcotics Act Nepal)
    |--------------------------------------------------------------------------
    */

    'schedule_types' => [
        'P' => 'Prescription Only',
        'H' => 'Hospital Only',
        'N' => 'Narcotic',
        'OTC' => 'Over The Counter',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dosage Forms
    |--------------------------------------------------------------------------
    */

    'dosage_forms' => [
        'tablet',
        'capsule',
        'syrup',
        'suspension',
        'injection',
        'cream',
        'ointment',
        'drops',
        'inhaler',
        'suppository',
        'powder',
        'gel',
        'lotion',
        'patch',
        'spray',
        'solution',
        'emulsion',
        'paste',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unit Types for Inventory
    |--------------------------------------------------------------------------
    */

    'unit_types' => [
        'piece' => ['label' => 'Piece', 'abbreviation' => 'pc'],
        'strip' => ['label' => 'Strip', 'abbreviation' => 'strip'],
        'bottle' => ['label' => 'Bottle', 'abbreviation' => 'btl'],
        'tube' => ['label' => 'Tube', 'abbreviation' => 'tube'],
        'box' => ['label' => 'Box', 'abbreviation' => 'box'],
        'vial' => ['label' => 'Vial', 'abbreviation' => 'vial'],
        'ampoule' => ['label' => 'Ampoule', 'abbreviation' => 'amp'],
        'sachet' => ['label' => 'Sachet', 'abbreviation' => 'sachet'],
        'roll' => ['label' => 'Roll', 'abbreviation' => 'roll'],
        'pack' => ['label' => 'Pack', 'abbreviation' => 'pkt'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    */

    'payment_methods' => [
        'cash' => 'Cash',
        'esewa' => 'eSewa',
        'khalti' => 'Khalti',
        'ime_pay' => 'IME Pay',
        'connectips' => 'ConnectIPS',
        'card' => 'Card',
        'credit' => 'Credit (Due)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prescription Validity (days)
    |--------------------------------------------------------------------------
    */

    'prescription_validity_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    */

    'low_stock_threshold' => (int) env('PHARMACY_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Expiry Alert Threshold (days)
    |--------------------------------------------------------------------------
    */

    'expiry_alert_days' => (int) env('PHARMACY_EXPIRY_ALERT_DAYS', 90),

];
