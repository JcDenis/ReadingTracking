<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\{ Cursor, MetaRecord };
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Fieldset, Label, Legend, Note, Para, Select, Text };
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
     * User pref update.
     */
    public static function updateUser(Cursor $cur, string $user_id = ''): void
    {
        if (!empty($_POST[My::id() . '_allread'])) {
            ReadingTracking::markReadPosts($user_id);
        } elseif (!empty($_POST[My::id() . '_reset'])) {
            ReadingTracking::remarkReadPosts($user_id);
        }

        $user_prefs = App::userPreferences()->createFromUser($user_id, My::id());
        $user_prefs->get(My::id())->put('active', !empty($_POST[My::id() . '_active']), 'boolean');
        $user_prefs->get(My::id())->put('comment', !empty($_POST[My::id() . '_comment']), 'boolean');
    }

    /**
     * Current user pref form.
     */
    public static function preferencesForm(): void
    {

        echo (new Fieldset())
            ->id(My::id() . '_prefs')
            ->legend(new Legend(My::name()))
            ->fields(self::formForm((string) App::auth()->userID()))->render();
    }

    /**
     * User pref form.
     */
    public static function userForm(?MetaRecord $rs): void
    {
        echo (new Div())
            ->class('fieldset')
            ->items(self::formForm(is_null($rs) || $rs->isEmpty() ? '' : $rs->user_id))
            ->render();
    }

    /**
     * User pref form content.
     *
     * @return  array<int, Para>
     */
    protected static function formForm(string $user_id): array
    {
        $active = $comment = false;
        if (!empty($user_id)) {
            $prefs  = App::userPreferences()->createFromUser($user_id, My::id());
            $active  = (bool) $prefs->get(My::id())->get('active');
            $comment = (bool) $prefs->get(My::id())->get('comment');
        }

        return [
            (new Para())
                ->items([
                    (new Checkbox(My::id() . '_active', $active))
                        ->value('1')
                        ->label(new Label(__('Add artifact on unread entries'), Label::IL_FT)),
                ]),
            (new Para())
                ->items([
                    (new Checkbox(My::id() . '_comment', $comment))
                        ->value('1')
                        ->label(new Label(__('Reset a post reading tracking on new comment'), Label::IL_FT)),
                ]),
            (new Para())
                ->items([
                    (new Checkbox(My::id() . '_allread', false))
                        ->value('1')
                        ->label(new Label(__('Mark all entries as read'), Label::IL_FT)),
                ]),
            (new Para())
                ->items([
                    (new Checkbox(My::id() . '_reset', false))
                        ->value(1)
                        ->label(new Label(__('Reset reading tracking'), Label::IL_FT)),
                ]),
        ];
    }

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
