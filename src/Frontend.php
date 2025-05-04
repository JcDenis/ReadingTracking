<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       ReadingTracking module frontend process.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {

            return false;
        }

        App::frontend()->template()->addValue('EntryTitle', FrontendTemplate::EntryTitle(...));
        App::frontend()->template()->addBlock('ReadingTrackingIf', FrontendTemplate::ReadingTrackingIf(...));

        App::behavior()->addBehaviors([
            'publicPostBeforeGetPosts'    => FrontendBehaviors::publicPostBeforeGetPosts(...),
            'coreBlogGetPosts'            => FrontendBehaviors::coreBlogGetPosts(...),
            'publicFrontendSessionAction' => FrontendBehaviors::publicFrontendSessionAction(...),
            'publicFrontendSessionPage'   => FrontendBehaviors::publicFrontendSessionPage(...),
        ]);

        return true;
    }
}
