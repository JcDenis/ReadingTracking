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

if (!isset($this) || !is_object($this) || !method_exists($this, 'registerModule') || !isset($this->id) || !is_string($this->id)) {
    return;
}

$this->registerModule(
    'Reading tracking',
    'Mark post as read for connected users.',
    'Jean-Christian Paul Denis and Contributors',
    '0.14',
    [
        'requires'    => [
            ['core', '2.39'],
            ['FrontendSession', '0.42'],
        ],
        'settings'    => [
            'blog' => '#params.' . $this->id . '_params',
            'pref' => '#user-options.' . $this->id . '_prefs',
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-09-11T17:03:17+00:00',
    ]
);
