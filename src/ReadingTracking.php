<?php

declare(strict_types=1);

namespace Dotclear\Plugin\ReadingTracking;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\{ DeleteStatement, InsertStatement, SelectStatement };
use Dotclear\Helper\Html\Form\{ Img, Option };

/**
 * @brief       ReadingTracking core class.
 * @ingroup     ReadingTracking
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ReadingTracking
{
    public const META_TYPE        = 'ReadingTracking';
    public const DEFAULT_ARTIFACT = "\u{1F441}";

    /**
     * Get database table name (for query).
     */
    protected static function table(): string
    {
        return App::con()->prefix() . App::meta()::META_TABLE_NAME;
    }

    /**
     * Get user id (for query).
     */
    public static function user(): string
    {
        return (string) App::con()->escapeStr((string) App::auth()->userID());
    }

    /**
     * Get meta type (for query).
     */
    public static function type(): string
    {
        return self::META_TYPE;
    }

    /**
     * Mark a post as read for current user.
     */
    public static function setReadPost(int $post_id): void
    {
        if (!self::user()) {

            return;
        }

        self::delReadPost($post_id, false);

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
     * Mark all posts as read for current user.
     */
    public static function setReadPosts(): void
    {
        self::resetReadPosts();
        //how whitout killing db?

        $values = [];
        $rs = App::blog()->getPosts(['no_content' => true]);
        while($rs->fetch()) {
            $values[] = [
                self::user(),
                self::type(),
                (int) $rs->f('post_id'),
            ];
        }

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

    /**
     * Check if user has read a post.
     */
    public static function isReadPost(int $post_id):  bool
    {
        if (!self::user()) {

            return false;
        }

        $sql = new SelectStatement();
        $rs = $sql
            ->from(self::table())
            ->where('post_id = ' . $post_id)
            ->and('meta_type = ' . $sql->quote(self::type()))
            ->and('meta_id = ' . $sql->quote(self::user()))
            ->limit(1)
            ->select();

        return !is_null($rs) && !$rs->isEmpty();
    }

    /**
     * Delete a post tracking for a user or all users.
     */
    public static function delReadPost(int $post_id, bool $all_users = false): void
    {
        if (!$all_users && !self::user()) {
        
            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('post_id = ' . $post_id)
            ->and('meta_type = ' . $sql->quote(self::type()));

        if (!$all_users) {
            $sql->and('meta_id = ' . $sql->quote(self::user()));
        }

        $sql->delete();
    }

    /**
     * Delete all posts tracking for a user.
     */
    public static function resetReadPosts(): void
    {
        if (!self::user()) {
        
            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('meta_type = ' . $sql->quote(self::type()))
            ->and('meta_id = ' . $sql->quote(self::user()))
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
     * @return  array<int, string>
     */
    public static function getArtifacts(): array
    {
        return [
            self::DEFAULT_ARTIFACT,
            "\u{23F5}",
            "\u{23F0}",
            "\u{2605}",
            "\u{2606}",
        ];
    }

    /**
     * @return array<in, Option>
     */
    public static function getArtifactsCombo(): array
    {
        $options = [
            __('Do not use artifact') => '',
        ];
        foreach (self::getArtifacts() as $artifact) {
            $options[] = new Option(
                $artifact,
                $artifact
            );
        }

        return $options;
    }
}