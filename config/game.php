<?php

declare(strict_types=1);

/*
 * Game balance settings (the legacy `settings` table and hard-coded values).
 */
return [
    'crystals_name' => 'Crystals',

    // Legacy bank.php / cyberbank.php. Fees round up and are capped at the max.
    'banks' => [
        'bank' => [
            'name' => 'Bank',
            'url' => '/bank',
            'column' => 'bank_balance',
            'cost' => 50_000,
            'deposit_fee_percent' => 15,
            'deposit_max_fee' => 3_000,
            'withdraw_fee_percent' => 0,
            'withdraw_max_fee' => 0,
            'daily_interest_percent' => 2,
        ],
        'cyber' => [
            'name' => 'Cyber Bank',
            'url' => '/cyber-bank',
            'column' => 'cyber_balance',
            'cost' => 10_000_000,
            'deposit_fee_percent' => 15,
            'deposit_max_fee' => 1_500_000,
            'withdraw_fee_percent' => 7.5,
            'withdraw_max_fee' => 750_000,
            'daily_interest_percent' => 7,
        ],
    ],

    // Legacy settings ct_refillprice, ct_iqpercrys, ct_moneypercrys.
    'crystal_temple' => [
        'refill_price' => 12,
        'iq_per_crystal' => 5,
        'money_per_crystal' => 200,
    ],

    // Run every five minutes by `php bin/cron.php five-minutes`.
    'regen' => [
        'energy_percent' => 8,
        'brave_percent' => 10,
        'hp_percent' => 33,
        'will' => 10,
    ],

    // Legacy creategang.php and yourgang.php.
    'gangs' => [
        'create_cost' => 500_000,
        'starting_capacity' => 5,
        'starting_respect' => 100,
        'capacity_slot_cost' => 100_000,
        'organised_crime_hours' => 24,
    ],

    // Legacy slotsmachine.php.
    'slots' => [
        'max_bet_per_level' => 250,
    ],

    // Legacy donator packs (staff_special.php givedp, donator.php). days extends donator status.
    'donator_packs' => [
        'standard' => ['name' => 'Pack 1 (Standard)', 'money' => 5000, 'crystals' => 50, 'iq' => 50, 'days' => 30],
        'crystals' => ['name' => 'Pack 2 (Crystals)', 'money' => 0, 'crystals' => 100, 'iq' => 0, 'days' => 30],
        'iq' => ['name' => 'Pack 3 (IQ)', 'money' => 0, 'crystals' => 0, 'iq' => 120, 'days' => 30],
        'five' => ['name' => 'Pack 4 ($5.00)', 'money' => 15000, 'crystals' => 75, 'iq' => 80, 'days' => 55],
        'ten' => ['name' => 'Pack 5 ($10.00)', 'money' => 35000, 'crystals' => 160, 'iq' => 180, 'days' => 115],
    ],
];
