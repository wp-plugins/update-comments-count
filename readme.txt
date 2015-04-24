=== Update Comments Count ===
Contributors: blogestudio, pauiglesias
Tags: comment, comments, update comments, fix, fix comments, count, counter, counters, comment counter, comments counter, posts comments
Requires at least: 3.3.2
Tested up to: 4.2
Stable tag: 1.0
License: GPLv2 or later

An easy way to update post comments counters, even for large sites, using WordPress standar function.

== Description ==

This plugin provides a simple method to update the comment counters stored in each post.

Works with multiple AJAX calls and processes packs of 50 posts by default.

For each entry, the comments counter is updated calling the WordPress function <code>wp_update_comment_count_now</code>.

Allows support for existing custom post types, and gives some plugin filters to change core configuration.

== Installation ==

1. Unzip and upload update-comments-count folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to menu Tools > Update Comments Count to start the updating process

== Frequently Asked Questions ==

= Why I need to update the posts comments counter? =

Sometimes, when interacting with another plugins that deals with comments, the commments counter originally computed by Wordpress can be wrong. This plugin solves this problem using massively the standar WordPress way.

== Screenshots ==

1. Updating posts comments counter via AJAX

== Changelog ==

= 1.0 =
Release Date: April 23th, 2015

* First and tested released until WordPress 4.2
* Tested code from WordPress 3.3.2 version.

== Upgrade Notice ==

= 1.0 =
Initial Release.