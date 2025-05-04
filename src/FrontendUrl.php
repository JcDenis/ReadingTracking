<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\Network\Http;

/**
 * @brief       ReadingTracking module URL handler.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendUrl extends Url
{
    /**
     * Get artifact.
     */
    public static function artifactEndpoint(?string $args): void
    {
        if (is_null($args) || !is_numeric($args) || !My::settings()->get('active')) {
            self::p404();
        }

        $rs = App::blog()->getPosts(['post_id' => (int) $args]);

        Http::head(200);
        echo json_encode([
            'ret' => $rs->isEmpty() ? '' : $rs->getArtifact(),
        ]);
        exit;
    }
}
