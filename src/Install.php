<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       ReadingTracking installation class.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            if (($s = My::settings()) !== null) {
                $s->put(
                    'active',
                    false,
                    'boolean',
                    'Enable reading tracking',
                    false,
                    true
                );
                $s->put(
                    'artifact',
                    ReadingTracking::DEFAULT_ARTIFACT,
                    'string',
                    'Default post artifact',
                    false,
                    true
                );
                $s->put(
                    'email_from',
                    '',
                    'string',
                    'Email notification sender',
                    false,
                    true
                );
                $s->put(
                    'url_types',
                    'post,category,tag,search,archive',
                    'string',
                    'Public URL type supported',
                    false,
                    true
                );
            }

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
