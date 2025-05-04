<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Tpl;

/**
 * @brief       ReadingTracking module template specifics.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendTemplate
{
    /**
     * Generic filter helper.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    private static function filter(ArrayObject $attr, string $res): string
    {
        return '<?php echo ' . sprintf(App::frontend()->template()->getFilters($attr), $res) . '; ?>';
    }

    /**
     * Check reading tracking conditions.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function ReadingTrackingIf(ArrayObject $attr, string $content): string
    {
        $if   = [];
        $sign = fn ($a): string => (bool) $a ? '' : '!';

        $operator = isset($attr['operator']) ? Tpl::getOperator($attr['operator']) : '&&';

        if (isset($attr['is_read'])) {
            $if[] = $sign($attr['is_read']) . "App::frontend()->context()->posts->isReadPost()";
        }
        if (isset($attr['use_artifact'])) {
            $if[] = $sign($attr['use_artifact']) . ReadingTracking::class . "::useArtifact()";
        }

        return $if === [] ?
            $content :
            '<?php if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * Get reading tracking artifact.
     *
     * This does not take care if user use artifact, for this use self::ReadingTrackingIf()
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function ReadingTrackingArtifact(ArrayObject $attr): string
    {
        return self::filter($attr, ReadingTracking::class . '::getArtifact()');
    }

    /**
     * Overload entry title to add artifact.
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public static function EntryTitle(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::frontend()->context()->posts->getArtifact() . " " . App::frontend()->context()->posts->post_title');
    }
}
