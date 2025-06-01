<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\{ Cursor, MetaRecord };
use Dotclear\Helper\Html\Form\{ Checkbox, Form, Hidden, Label, Submit };
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\FrontendSession\FrontendSessionProfil;

/**
 * @brief       ReadingTracking module frontend behaviors.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendBehaviors
{
    /**
     * Supported URL types.
     *
     * @var     null|array<int,string>   $types
     */
    private static ?array $types = null;

    /**
     * Extend posts record.
     * 
     * @see     RecordExtendPost
     */
    public static function coreBlogGetPosts(MetaRecord $rs): void
    {
        $rs->extend(RecordExtendPost::class);
    }

    /**
     * Remove reading tracking on new comment.
     *
     * @param   array<int, int>     $comments
     * @param   array<int, int>     $posts
     */
    public static function coreBlogAfterTriggerComments(array $comments, array $posts): void
    {
        if (My::prefs()->get('comment')) {
            foreach($posts as $post_id) {
                ReadingTracking::delReadPost((int) $post_id);
            }
        }
    }

    /**
     * Set post as read.
     *
     * @param   ArrayObject<string, mixed>  $params
     */
    public static function publicPostBeforeGetPosts(ArrayObject $params, ?string $args): void
    {
        $rs = App::blog()->getPosts($params);
        if (!$rs->isEmpty()) {
            ReadingTracking::markReadPost((int) $rs->f('post_id'));
        }
    }

    /**
     * Add JS for tracking to page headers.
     */
    public static function publicHeadContent(): string
    {
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
        if (in_array($tplset, ['dotty', /*'mustek'*/])) {
            echo My::cssLoad('frontend-' . $tplset);
        }

        if (self::isURLType() && ReadingTracking::useArtifact()) {
            echo My::jsLoad('frontend') .
            Html::jsJson(My::id(), ['url' => App::blog()->url() . App::url()->getBase(My::id()) . '/']);
        }

        return '';
    }

    /**
     * Add subscribe button after post content.
     */
    public static function publicEntryAfterContent(): void
    {
        if (My::settings()->get('active')
            && App::auth()->userId() != ''
            && self::isURLType()
        ) {
            $post_id = (int) App::frontend()->context()->posts->f('post_id');
            $check   = ReadingTracking::isSubscriber($post_id);

            if (!App::frontend()->context()->posts->commentsActive()) {

                return;
            }

            if (!empty($_POST[My::id() . 'post']) 
                && $post_id == (int) $_POST[My::id() . 'post']
            ) {
                ReadingTracking::checkForm();

                $check   = !$check;

                if ($check) {
                    ReadingTracking::addSubscriber($post_id);
                } else {
                    ReadingTracking::delSubscriber($post_id);
                }
            }

            echo (new Form(My::id(). $post_id))
                ->method('post')
                ->action('#p' . $post_id)
                ->class('post-reading-tracking')
                ->fields([
                    (new Submit([My::id() . 'subscribe'], $check ? __('Unsubscribe') : __('Subscribe')))
                        ->title($check ? __('No more recieve email when new comment is posted') : __('Receive an email when new comment is posted')),
                    (new Hidden([My::id() .'check'], App::nonce()->getNonce())),
                    (new Hidden([My::id() .'post'], (string) $post_id)),
                ])
                ->render();
        }
    }

    /**
     * Send mail to post subscribers on new comment.
     *
     * This works only if comments a created and put online from frontend !
     */
    public static function publicAfterCommentCreate(Cursor $cur, int|string $comment_id): void
    {
        if (My::settings()->get('active') && !App::status()->comment()->isRestricted((int) $cur->getField('comment_status'))) {
            ReadingTracking::mailSubscribers((int) $cur->getField('post_id'), $cur->comment_email ?? '');
        }
    }

    /**
     * Save session page user settings.
     */
    public static function FrontendSessionAction(string $action): void
    {
        if ($action == My::id()
            && My::settings()->get('active')
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            $old_comment = (bool) My::prefs()->get('comment');
            $new_comment = !empty($_POST[My::id() . '_comment']);

            if ($old_comment !== $new_comment) {
                ReadingTracking::switchReadType($new_comment);
            }

            My::prefs()->put('comment', !empty($_POST[My::id() . '_comment']), 'boolean');
            My::prefs()->put('active', !empty($_POST[My::id() . '_active']), 'boolean');

            if (!empty($_POST[My::id() . '_allread'])) {
                ReadingTracking::markReadPosts();
            }
            if (!empty($_POST[My::id() . '_unsubscribe'])) {
                ReadingTracking::resetSubscriber();
            }

            // need to reload user to update form values
            App::auth()->checkUser((string) App::auth()->userID());

            App::frontend()->context()->frontend_session->success = __('Profil successfully updated.');
        }
    }

    /**
     * Add session page user settings form.
     */
    public static function FrontendSessionProfil(FrontendSessionProfil $profil): void
    {
        if (My::settings()->get('active')
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            $profil->addAction(My::id(), __('Reading tracking'), [
                $profil->getInputfield([
                    (new Checkbox(My::id() . '_active', !empty(My::prefs()->get('active'))))
                        ->value('1')
                        ->label(new Label(__('Add reading tracking icon on unread entries'), Label::OL_FT)),
                ]),
                $profil->getInputfield([
                    (new Checkbox(My::id() . '_comment', !empty(My::prefs()->get('comment'))))
                        ->value('1')
                        ->label(new Label(__('Reset a post reading tracking on new comment'), Label::OL_FT)),
                ]),
                $profil->getInputfield([
                    (new Checkbox(My::id() . '_allread', false))
                        ->value('1')
                        ->label(new Label(__('Mark all entries as read'), Label::OL_FT)),
                ]),
                $profil->getInputfield([
                    (new Checkbox(My::id() . '_unsubscribe', false))
                        ->value('1')
                        ->label(new Label(__('Remove email notifiaction from all entries'), Label::OL_FT)),
                ]),
                $profil->getControlset(My::id(), __('Save')),
            ]);
        }
    }

    private static function isURLType(): bool
    {
        if (is_null(self::$types)) {
            /**
             * @var     ArrayObject<int, string>    $types
             */
            $types = new ArrayObject(explode(',', My::settings()->get('url_types')));

            # --BEHAVIOR-- ReadingTrackingUrlTypes -- ArrayObject
            App::behavior()->callBehavior('ReadingTrackingUrlTypes', $types);

            self::$types = iterator_to_array($types);
        }

        return in_array(App::url()->getType(), self::$types);
    }
}
