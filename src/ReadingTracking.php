<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\{ DeleteStatement, InsertStatement, JoinStatement, SelectStatement, UpdateStatement };
use Dotclear\Exception\PreconditionException;
use Dotclear\Helper\Html\Form\{ Img, Option };
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\Mail\Mail;

/**
 * @brief       ReadingTracking core class.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ReadingTracking
{
    public const META_TRACK_POST    = 'track_read_post';
    public const META_TRACK_COMMENT = 'track_read_comment';
    public const META_MAIL_COMMENT  = 'mail_new_comment';


    public const META_READING     = 'ReadingTracking';
    public const META_MAILING     = 'MailingTracking';
    public const DEFAULT_ARTIFACT = "\u{1F441}";

    private static ?string $user = null;

    /**
     * Get database table name (for query).
     */
    protected static function table(): string
    {
        return App::con()->prefix() . App::meta()::META_TABLE_NAME;
    }

    /**
     * Get user id.
     */
    public static function user(): string
    {
        // check once
        if (is_null(self::$user)) {
            self::$user = My::settings()->get('active') && App::auth()->check(My::id(), App::blog()->id()) ? (string) (string) App::auth()->userID() : '';
        }

        return self::$user;
    }

    /**
     * Get type of trakcing for current user.
     */
    public static function type(): string
    {
        return My::prefs()->get('comment') ? self::META_TRACK_COMMENT : self::META_TRACK_POST;
    }

    /**
     * Get types of post tracking.
     *
     * @return  array<int, string>
     */
    public static function types(): array
    {
        return [self::META_TRACK_POST, self::META_TRACK_COMMENT];
    }

    /**
     * Switch current user type of tracking.
     */
    public static function switchReadType(bool $to_comment = false): void
    {
        if (($posts = self::getReadPosts()) !== []) {
            $sql = new UpdateStatement();
            $sql
                ->from(self::table())
                ->set('meta_type = ' . $sql->quote($to_comment ? self::META_TRACK_COMMENT : self::META_TRACK_POST))
                ->where('post_id' . $sql->in($posts, 'int'))
                ->and('meta_type ' . $sql->in(self::types()))
                //->and('meta_type = ' . $sql->quote(!$to_comment ? self::META_TRACK_COMMENT : self::META_TRACK_POST))
                ->and('meta_id = ' . $sql->quote(self::user()))
                ->update();
        }
    }

    /**
     * Mark a post as read for current user.
     */
    public static function markReadPost(int $post_id): void
    {
        if (self::user() === '') {

            return;
        }

        self::delReadPost($post_id);

        $sql = new InsertStatement();
        $sql
            ->into(self::table())
            ->columns([
                'meta_id',
                'meta_type',
                'post_id',
            ])
            ->values([[
                self::user(),
                self::type(),
                $post_id,
            ]])
            ->insert();
    }

    /**
     * Check if current user has read a post.
     */
    public static function isReadPost(int $post_id):  bool
    {
        if (self::user() === '') {

            return false;
        }

        $sql = new SelectStatement();
        $rs = $sql
            ->from(self::table())
            ->where('post_id = ' . $post_id)
            ->and('meta_type' . $sql->in(self::types()))
            ->and('meta_id = ' . $sql->quote(self::user()))
            ->limit(1)
            ->select();

        return !is_null($rs) && !$rs->isEmpty();
    }

    /**
     * Delete a post tracking for current user on new comment.
     */
    public static function delReadPost(int $post_id): void
    {
        if (self::user() === '') {
        
            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('post_id = ' . $post_id)
            ->and('meta_type ' . $sql->in(self::types()))
            //->and('meta_type = ' . $sql->quote(self::META_TRACK_COMMENT))
            ->and('meta_id = ' . $sql->quote(self::user()))
            ->delete();
    }

    /**
     * Get read posts for current user.
     *
     * @return  array<int, int>
     */
    public static function getReadPosts(): array
    {
        if (self::user() === '') {

            return [];
        }

        $sql = new SelectStatement();
        $rs = $sql
            ->column('P.post_id')
            ->from($sql->as(self::table(), 'M'))
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('P.post_id = M.post_id')
                    ->statement()
            )
            ->where('M.meta_type' . $sql->in(self::types()))
            ->and('M.meta_id = ' . $sql->quote(self::user()))
            ->and('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->select();

        $res = [];
        if (!is_null($rs)) {
            while($rs->fetch()) {
                $res[] = (int) $rs->f('post_id');
            }
        }

        return $res;
    }

    /**
     * Mark all posts as read for a user.
     */
    public static function markReadPosts(string $user_id = ''): void
    {
        self::delReadPosts($user_id);

        if ($user_id === '' && self::user() === ''
            || $user_id !== '' && !App::auth()->isSuperAdmin()
        ) {

            return;
        }
        if ($user_id === '') {
            $user_id = self::user();
        }

        self::delReadPosts($user_id);

        $values = [];
        $reads  = self::getReadPosts();
        $posts  = App::blog()->getPosts(['no_content' => true]);
        while($posts->fetch()) {
            if (in_array((int) $posts->f('post_id'), $reads)) {
                continue;
            }
            $values[] = [
                $user_id,
                self::type(),
                (int) $posts->f('post_id'),
            ];
        }

        if (!empty($values)) {
            $sql = new InsertStatement();
            $sql
                ->into(self::table())
                ->columns([
                    'meta_id',
                    'meta_type',
                    'post_id',
                ])
                ->values($values)
                ->insert();
        }
    }

    /**
     * Delete all posts tracking for current user.
     */
    public static function delReadPosts(string $user_id = ''): void
    {
        if ($user_id === '' && self::user() === ''
            || $user_id !== '' && !App::auth()->isSuperAdmin()
        ) {

            return;
        }
        if ($user_id === '') {
            $user_id = self::user();
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('meta_type' . $sql->in(self::types()))
            ->and('meta_id = ' . $sql->quote($user_id))
            ->and('post_id IN (' .
                (new SelectStatement())
                    ->column('post_id')
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                    ->statement() .
            ')')
            ->delete();
    }

    /**
     * Check if blog use artifact.
     */
    public static function useArtifact(): bool
    {
        return My::settings()->get('active') && My::settings()->get('artifact') != '' && My::prefs()->get('active');
    }

    /**
     * Get current artifact.
     */
    public static function getArtifact(): string
    {
        return My::settings()->get('artifact') ?: self::DEFAULT_ARTIFACT;
    }

    /**
     * Get artifacts list.
     *
     * @return  array<int, string>
     */
    public static function getArtifacts(): array
    {
        return array_unique([
            My::settings()->get('artifact') ?: self::DEFAULT_ARTIFACT,
            self::DEFAULT_ARTIFACT,
            "\u{23F5}",
            "\u{23F0}",
            "\u{2605}",
            "\u{2606}",
        ]);
    }

    /**
     * Get artifacts combo.
     *
     * @return array<int, Option>
     */
    public static function getArtifactsCombo(): array
    {
        $options = [
            new Option(__('Do not use artifact'), ''),
        ];
        foreach (self::getArtifacts() as $artifact) {
            $options[] = new Option(
                $artifact,
                $artifact
            );
        }

        return $options;
    }

    /**
     * Add current user to a post mailing list.
     */
    public static function addSubscriber(int $post_id): void
    {
        if (self::user() === '') {

            return;
        }

        self::delSubscriber($post_id);

        $sql = new InsertStatement();
        $sql
            ->into(self::table())
            ->columns([
                'meta_id',
                'meta_type',
                'post_id',
            ])
            ->values([[
                self::user(),
                self::META_MAILING,
                $post_id,
            ]])
            ->insert();
    }

    /**
     * Delete current user from a post mailing list.
     */
    public static function delSubscriber(int $post_id): void
    {
        if (self::user() === '') {

            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('meta_type = ' . $sql->quote(self::META_MAILING))
            ->and('meta_id = ' . $sql->quote(self::user()))
            ->and('post_id = ' . $post_id)
            ->delete();
    }

    /**
     * Check if current user is in a post mailing list.
     */
    public static function isSubscriber(int $post_id): bool
    {
        if (self::user() === '') {

            return false;
        }

        return !App::meta()->getMetadata([
            'post_id' => $post_id,
            'meta_id' => self::user(),
            'meta_type' => self::META_MAILING
        ])->isEmpty();
    }

    /**
     * Remove current user from all posts mailing lists.
     */
    public static function resetSubscriber(string $user_id = ''): void
    {
        if (self::user() === '') {

            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('meta_type = ' . $sql->quote(self::META_MAILING))
            ->and('meta_id = ' . $sql->quote(self::user()))
            ->delete();
    }

    /**
     * Get users that in a post mailing list.
     *
     * @return  array<string, string>   The users id/email
     */
    public static function getSubscribers(int $post_id): array
    {
        $sql = new SelectStatement();
        $rs = $sql
            ->column('U.user_id, U.user_email')
            ->from($sql->as(self::table(), 'M'))
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('P.post_id = M.post_id')
                    ->statement()
            )
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->on('U.user_id = M.meta_id')
                    ->statement()
            )
            ->where('M.meta_type = ' . $sql->quote(self::META_MAILING))
            ->and('M.post_id = ' . $post_id)
            ->and('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->select();

        $res = [];
        if (!is_null($rs)) {
            while($rs->fetch()) {
                $res[(string) $rs->f('user_id')] = (string) $rs->f('user_email');
            }
        }

        return $res;
    }

    /**
     * Send mail to a post mainling list on new post comments.
     */
    public static function mailSubscribers(int $post_id): void
    {
        if (!My::settings()->get('email_from')) {

            return;
        }

        $mails = self::getSubscribers($post_id);
        if ($mails === []) {

            return;
        }

        $rs = App::blog()->getPosts(['post_id' => $post_id, 'no_content' => true, 'limit' => 1]);
        if ($rs->isEmpty()) {
            return;
        }

        $headers = [
            'From: ' . sprintf('%s <%s>', Mail::B64Header(App::blog()->name()), My::settings()->get('email_from')),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8;',
            'Content-Transfer-Encoding: 8bit',
            'X-Originating-IP: ' . Http::realIP(),
            'X-Mailer: Dotclear',
            'X-Blog-Id: ' . Mail::B64Header(App::blog()->id()),
            'X-Blog-Name: ' . Mail::B64Header(App::blog()->name()),
            'X-Blog-Url: ' . Mail::B64Header(App::blog()->url()),
        ];

        $subject = Mail::B64Header(sprintf('[%s] %s', App::blog()->name(), __('New comment')));

        $message = wordwrap(
            __('You receive this email as you have subscribed to new comments on an entry.') . "\n\n" .
            __('Site:') . ' ' . App::blog()->name() . ' - ' . App::blog()->url() . "\n" .
            __('Entry:') . ' ' . $rs->f('post_title') . "\n" .
            __('URL:') . ' ' . $rs->getURL() . "\n\n" .
            __('Follow this link to see new comment or to unsubscribe to this entry.') . "\n" .
            sprintf(__('Thank you %s for your interest on our blog.'), '#$USER$#') . "\n",
            80
        );

        foreach ($mails as $user => $mail) {
            Mail::sendMail($mail, $subject, str_replace('#$USER$#', $user, $message), $headers);
        }
    }

    /**
     * Check nonce from POST requests.
     */
    public static function checkForm(): void
    {
        if (!App::nonce()->checkNonce($_POST[My::id() . 'check'] ?? '-')) {
            throw new PreconditionException();
        }
    }
}