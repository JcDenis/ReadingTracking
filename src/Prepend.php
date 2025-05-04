<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       ReadingTracking module prepend.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Add URL to get artifact from js
        App::url()->register(
            My::id(),
            'artifact',
            '^artifact/(.+)$',
            FrontendUrl::artifactEndpoint(...)
        );

        return true;
    }
}
