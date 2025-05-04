<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @brief       ReadingTracking module record extension.
 * @ingroup     ReadingTracking
 *
 * @author      Dotclear team
 * @copyright   AGPL-3.0
 */
class RecordExtendPost
{
    /**
     * Check if user has read this post.
     */
    public static function isReadPost(MetaRecord $rs): bool
    {
        return ReadingTracking::isReadPost((int) $rs->f('post_id'));
    }

    /**
     * Get artifact (if user have not read this post).
     */
    public static function getArtifact(MetaRecord $rs): string
    {
        return !ReadingTracking::isReadPost((int) $rs->f('post_id')) && ReadingTracking::useArtifact() ? ReadingTracking::getArtifact() : ''; 
    }
}
