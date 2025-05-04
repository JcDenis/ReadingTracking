<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Exception\PreconditionException;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Form, Hidden, Label, Submit, Text };
use Dotclear\Helper\Network\Http;

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
     * Extend posts record.
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
        if (My::settings()->get('active')
            && App::auth()->check(My::id(), App::blog()->id())
            && My::prefs()->get('comment')
        ) {
            foreach($posts as $post) {
                ReadingTracking::delReadPost((int) $post);
            }
        }
    }

    public static function publicFrontendSessionAction(string $action): void
    {
        if ($action == 'rtupd'
            && My::settings()->get('active')
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            My::prefs()->put('comment', !empty($_POST[My::id() . $action . '_comment']), 'boolean');
            My::prefs()->put('active', !empty($_POST[My::id() . $action . '_active']), 'boolean');

            if (!empty($_POST[My::id() . $action . '_allread'])) {
                ReadingTracking::markReadPosts();
            }

            // need to reload user to update form values
            App::auth()->checkUser((string) App::auth()->userID());

            App::frontend()->context()->frontend_session->success = __('Profil successfully updated.');
        }
    }

    public static function publicFrontendSessionPage(): void
    {
        if (My::settings()->get('active')
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            $action = 'rtupd';
            echo (new Div(My::id() . $action))
            ->items([
                (new Text('h3', __('Reading tracking'))),
                (new Form(My::id() . $action . 'form'))
                    ->class('session-form')
                    ->action('')
                    ->method('post')
                    ->fields([
                        (new Div())
                            ->class('inputfield')
                            ->items([
                                (new Checkbox(My::id() . $action . '_active', !empty(My::prefs()->get('active'))))
                                    ->value('1')
                                    ->label(new Label(__('Add artifact on unread entries'), Label::OL_FT)),
                            ]),
                        (new Div())
                            ->class('inputfield')
                            ->items([
                                (new Checkbox(My::id() . $action . '_comment', !empty(My::prefs()->get('comment'))))
                                    ->value('1')
                                    ->label(new Label(__('Reset a post reading tracking on new comment'), Label::OL_FT)),
                            ]),
                        (new Div())
                            ->class('inputfield')
                            ->items([
                                (new Checkbox(My::id() . $action . '_allread', false))
                                    ->value('1')
                                    ->label(new Label(__('Mark all entires as read'), Label::OL_FT)),
                            ]),
                        (new Div())
                            ->class('controlset')
                            ->items([
                                (new Submit(My::id() . $action . 'save', __('Save')))
                                    ->class('button'),
                                (new Hidden(['FrontendSessionredir'], Http::getSelfURI())),
                                (new Hidden(['FrontendSessioncheck'], App::nonce()->getNonce())),
                                (new Hidden(['FrontendSessionaction'], $action)),
                            ]),
                    ]),
            ])
            ->render();
        }
    }
}
