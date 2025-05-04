<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Label, Note, Para, Select, Text };
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\BlogSettingsInterface;

/**
 * @brief       ReadingTracking module backend behaviors.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class BackendBehaviors
{
    /**
     * Blog pref form.
     */
    public static function adminBlogPreferencesFormV2(BlogSettingsInterface $blog_settings): void
    {
        echo (new Div())
            ->class('fieldset')
            ->items([
                (new Text('h4', My::name()))
                    ->id(My::id() . '_params'),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'active', (bool) $blog_settings->get(My::id())->get('active')))
                            ->value(1)
                            ->label(new Label(__('Enable user reading tracking'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Select(My::id() . 'artifact'))
                            ->items(ReadingTracking::getArtifactsCombo())
                            ->default((string) $blog_settings->get(My::id())->get('artifact'))
                            ->label((new Label(__('Artifact to use:'), Label::OL_TF))),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('This add artifact at the begining of unread posts titles. Or you can use custom templates instead.')),
            ])
            ->render();
    }

    /**
     * Blog pref update.
     */
    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $blog_settings): void
    {
        $change = $blog_settings->get(My::id())->get('artifact') !== $_POST[My::id() . 'artifact'];

        $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']));
        $blog_settings->get(My::id())->put('artifact', (string) $_POST[My::id() . 'artifact']);

        // Need to clean template EntryTitle
        if ($change) {
            App::cache()->emptyTemplatesCache();
        }
    }
}
