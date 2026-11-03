<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Submit;
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
        // Mark posts as unread for users that reset reading tracking on new comment
        ReadingTracking::delCommentsReadPosts($posts);
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
            ReadingTracking::markReadPost($rs->intField('post_id'));
        }
    }

    /**
     * Add JS for tracking to page headers.
     */
    public static function publicHeadContent(): string
    {
        $tplset = App::themes()->moduleInfo((string) App::blog()->settings()->get('system')->getStr('theme'), 'tplset');
        if (is_string($tplset) && in_array($tplset, ['dotty', /*'mustek'*/], true)) {
            echo My::cssLoad('frontend-' . $tplset);
        }

        if (self::isURLType() && ReadingTracking::useArtifact()) {
            echo My::jsLoad('frontend') .
            Html::jsJson(My::id(), ['url' => App::blog()->url() . App::url()->getBase(My::id()) . '/']);
        }

        return '';
    }

    /**
     * Add or remove post subscription.
     */
    public static function FrontendSessionPostAction(MetaRecord $post): void
    {
        if (My::settings()->getBool('active')
            && self::isURLType()
            && $post->commentsActive()
            && !empty($_POST[My::id() . 'subscribe'])
        ) {
            $post_id = $post->intField('post_id');

            if (!ReadingTracking::isSubscriber($post_id)) {
                ReadingTracking::addSubscriber($post_id);
            } else {
                ReadingTracking::delSubscriber($post_id);
            }
        }
    }

    /**
     * Add subscribe button after post content.
     * 
     * @param   ArrayObject<int, Submit>    $buttons
     */
    public static function FrontendSessionPostForm(MetaRecord $post, ArrayObject $buttons): void
    {
        if (My::settings()->getBool('active')
            && self::isURLType()
            && $post->commentsActive()
        ) {
            $check = ReadingTracking::isSubscriber($post->intField('post_id'));

            $buttons->append(
                (new Submit([My::id() . 'subscribe'], $check ? __('Unsubscribe') : __('Subscribe')))
                    ->title($check ? __('No more recieve email when new comment is posted') : __('Receive an email when new comment is posted'))
            );
        }
    }

    /**
     * Send mail to post subscribers on new comment.
     *
     * This works only if comments a created and put online from frontend !
     */
    public static function publicAfterCommentCreate(Cursor $cur, int|string $comment_id): void
    {
        $status  = is_numeric($status = $cur->getField('comment_status')) ? (int) $status : 0;
        $post_id = is_numeric($post_id = $cur->getField('post_id')) ? (int) $post_id : 0;
        $email   = is_string($email = $cur->getField('comment_email')) ? $email : '';
        if (My::settings()->getBool('active') && $post_id !== 0 && !App::status()->comment()->isRestricted($status)) {
            ReadingTracking::mailSubscribers($post_id, $email);
        }
    }

    /**
     * Save session page user settings.
     */
    public static function FrontendSessionAction(string $action): void
    {
        if ($action === My::id()
            && My::settings()->getBool('active')
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            $old_comment = (bool) My::prefs()->getBool('comment');
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

            if (App::frontend()->context()->frontend_session instanceof \Dotclear\Plugin\FrontendSession\FrontendSession) {
                App::frontend()->context()->frontend_session->success = __('Profil successfully updated.');
            }
        }
    }

    /**
     * Add session page user settings form.
     */
    public static function FrontendSessionProfil(FrontendSessionProfil $profil): void
    {
        if (My::settings()->getBool('active')
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            $profil->addAction(My::id(), __('Reading tracking'), [
                $profil->getInputfield([
                    (new Checkbox(My::id() . '_active', (bool) My::prefs()->getBool('active')))
                        ->value('1')
                        ->label(new Label(__('Add reading tracking icon on unread entries'), Label::OL_FT)),
                ]),
                $profil->getInputfield([
                    (new Checkbox(My::id() . '_comment', (bool) My::prefs()->getBool('comment')))
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
            $types = new ArrayObject(explode(',', (string) My::settings()->getStr('url_types')));

            # --BEHAVIOR-- ReadingTrackingUrlTypes -- ArrayObject
            App::behavior()->callBehavior('ReadingTrackingUrlTypes', $types);

            self::$types = iterator_to_array($types);
        }

        return App::url()->isType(self::$types);
    }
}
