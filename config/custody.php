<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Weekend anchor date
    |--------------------------------------------------------------------------
    |
    | The alternating Fri/Sat/Sun custody block is computed relative to this
    | Friday. The weekend block of the anchor week belongs to the father; it
    | flips parents every subsequent week.
    |
    */

    'anchor_date' => '2026-06-26',

    /*
    |--------------------------------------------------------------------------
    | Parents
    |--------------------------------------------------------------------------
    |
    | Each custody role has a display label and a color used to color-code the
    | calendar consistently per parent.
    |
    */

    'parents' => [
        'father' => [
            'label' => 'Father',
            'color' => '#2563eb', // blue-600
        ],
        'mother' => [
            'label' => 'Mother',
            'color' => '#db2777', // pink-600
        ],
    ],

];
