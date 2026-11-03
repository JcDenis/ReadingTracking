<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;

/**
 * @brief       ReadingTracking module URL handler.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendUrl
{
    /**
     * Get artifact.
     */
    public static function artifactEndpoint(?string $args): void
    {
        if (is_null($args) || !is_numeric($args) || !My::settings()->getBool('active')) {
            App::url()::p404();
        }

        $rs = App::blog()->getPosts(['post_id' => (int) $args, 'no_content' => true]);

        // Do not use Http::head(200) here, it sends a bad header on some servers
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ret' => $rs->isEmpty() ? '' : RecordExtendPost::getArtifact($rs),
        ]);
        dotclear_exit();
    }
}
