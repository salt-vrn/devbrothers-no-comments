=== DevBrothers No Comments ===
Contributors: lzolotarev
Tags: comments, disable, remove, delete, admin
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 1.0.1
Requires PHP: 7.4
Requires Plugins: devbrothers-admin-panel
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Completely disable comments on your WordPress site. Remove all existing comments with one click.

== Description ==

DevBrothers No Comments is a simple and powerful plugin that allows you to completely disable comments on your WordPress site. With just one click, you can turn off all comment functionality and remove all existing comments from your database.

= Features =

* **Global Comment Disabling**: Disable comments site-wide with a single checkbox
* **Complete Removal**: Remove all comments and comment metadata from the database with one click
* **REST API Support**: Comments are disabled through REST API endpoints
* **XML-RPC Support**: Comments are disabled through XML-RPC methods
* **Admin Integration**: Seamlessly integrated with DevBrothers Admin Panel
* **Safe and Secure**: All actions are protected with proper nonce verification and capability checks

= What Gets Disabled =

When you enable comment disabling, the plugin will:

* Remove comment forms from the frontend
* Disable comments through REST API
* Disable comments through XML-RPC
* Hide comment menu items in the admin area
* Remove comment widgets from the dashboard
* Block comment feeds (RSS)
* Remove X-Pingback headers

= Delete All Comments =

The plugin includes a powerful feature to delete all existing comments from your database. This action:

* Removes all comments from the database
* Removes all comment metadata
* Updates post comment counts
* Clears affected post caches

**Warning**: This action is irreversible. Make sure to backup your database before using this feature.

== Installation ==

1. Install and activate the DevBrothers Admin Panel plugin (required)
2. Install and activate DevBrothers No Comments
3. Go to DevBrothers → No Comments in the WordPress admin
4. Check "Disable comments globally on the entire site" and click "Save Settings"
5. Optionally, use the "Delete All Comments" button to remove existing comments

== Frequently Asked Questions ==

= Does this plugin require DevBrothers Admin Panel? =

Yes, DevBrothers No Comments is part of the DevBrothers plugin ecosystem and requires DevBrothers Admin Panel to be installed and activated.

= Will this plugin delete my comments automatically? =

No, the plugin only disables comments by default. You must manually click the "Delete All Comments" button if you want to remove existing comments from the database.

= Can I restore comments after deleting them? =

No, the delete action is permanent and irreversible. Always backup your database before using the delete feature.

= Does this plugin work with multisite? =

Currently, the plugin is designed for single-site installations. Multisite support may be added in future versions.

= Will this affect existing comments? =

When you disable comments, existing comments will remain in the database but will not be displayed or accessible. To completely remove them, use the "Delete All Comments" feature.

= I used the previous DevBrothers Disable Comments plugin. Will my settings be kept? =

Yes. If you replace the old plugin with DevBrothers No Comments, your saved settings are migrated automatically from the legacy option on first load.

== Screenshots ==

1. Main settings page with comment disabling option
2. Delete comments section with warning message
3. Success message after deleting comments

== Changelog ==

= 1.0.1 =
* Disable REST access to individual comments when global disabling is enabled.
* Check moderation permissions and delete comments through WordPress so deletion hooks and caches are handled.

= 1.0.0 =
* Initial release
* Global comment disabling
* Delete all comments feature
* REST API and XML-RPC support
* Integration with DevBrothers Admin Panel
* Automatic migration from legacy DevBrothers Disable Comments settings

== Upgrade Notice ==

= 1.0.1 =
Existing comments are not deleted by updating or disabling comments. The manual bulk delete action requires moderation and permission to edit each comment.

= 1.0.0 =
Initial release.
