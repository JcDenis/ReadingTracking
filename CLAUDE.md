# ReadingTracking

Notes for AI assistants working on the Dotclear plugin ReadingTracking.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Mark post as read for connected users.

No admin page. Public side: behaviors.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendBehaviors.php`: public behaviors.
* `src/FrontendTemplate.php`: template tags.
* `src/Install.php`: install and update, default settings.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/ReadingTracking.php`: core class.
* `src/RecordExtendPost.php`: module record extension.
* Behaviors used: `adminAfterUserCreate`, `adminAfterUserUpdate`, `adminBeforeUserOptionsUpdate`, `adminPreferencesFormV2`, `adminUserForm`, `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `coreBlogGetPosts`, `coreBlogAfterTriggerComments`, `publicPostBeforeGetPosts`, `publicHeadContent`, `urlHandlerServeDocument`, `publicAfterCommentCreate`.
* Requires FrontendSession (`requires` in `_define.php`).

## Pitfalls

* The unread icon is added server side to the HTML of the page (`urlHandlerServeDocument`): entries are found by `<article id="pID">` and their title by the class `post-title`.
* CI installs FrontendSession from the author's repositories, then from franck-paul's.

## Testing

* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
