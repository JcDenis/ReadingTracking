<?php

/**
 * @file
 * @brief       The plugin ReadingTracking definition
 * @ingroup     ReadingTracking
 *
 * @defgroup    ReadingTracking Plugin ReadingTracking.
 *
 * Mark post as read for connected users.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Reading tracking',
    'Mark post as read for connected users.',
    'Jean-Christian Paul Denis and Contributors',
    '0.2',
    [
        'requires'    => [
            ['core', '2.34'],
            ['FrontendSession', '0.20'],
        ],
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-05-04T10:16:15+00:00',
    ]
);
