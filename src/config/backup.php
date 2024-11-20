<?php

return [
    'chat_id' => '-4579352141',
    'bot_token' => '7513592647:AAHpKSy76qOjwzqo3_EfFDAALNvdHj63tHw',

    'prefix' => config('app.name'),
    'tag' => 'backup',

    'disks' => [
        'snapshots' => [
            'driver' => 'local',
            'root' => storage_path('app/snapshots'),
        ]
    ],
];