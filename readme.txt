=== Short URL ===

Author: SedLex
Contributors: SedLex
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/extend/plugins/shorten-url/
Tags: shorttag, shortag, bitly, url, short
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: trunk

Your pages/posts may have a short url hosted by your own domain.

== Description ==

Your pages/posts may have a short url hosted by your own domain.

Replace the internal function of wordpress get_short_link() by a bit.ly like url. 

Instead of having a short link like http://www.yourdomain.com/?p=3564, your short link will be http://www.yourdomain.com/NgH5z (for instance). 

You can configure: 

* the length of the short link, 
* if the link is prefixed with a static word, 
* the characters used for the short link.

Moreover, you can manage external links with this plugin. The links in your posts will be automatically replace by the short one if available.

This plugin is under GPL licence. 

= Localization =

* English (United States), default language
* French (France) translation provided by SedLex
* Russian (Russia) translation provided by Pacifik

= Features of the framework =

This plugin uses the SL framework. This framework eases the creation of new plugins by providing incredible tools and frames.

For instance, a new created plugin comes with

* A translation interface to simplify the localization of the text of the plugin ; 
* An embedded SVN client (subversion) to easily commit/update the plugin in wordpress.org repository ; 
* A detailled documentation of all available classes and methodes ; 
* etc.

Have fun !

== Installation ==

1. Upload this folder to your plugin directory (for instance '/wp-content/plugins/')
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'SL plugins' box
4. All plugins developed with the SL core will be listed in this box
5. Enjoy !

== Screenshots ==

1. The synthesis of the short link generated for your post and page
2. The configuration page of the plugin 

== Changelog ==

= 1.3.0 =
* Major release of the framework

= 1.2.3 =
* Russian translation (by Pacifik)

= 1.2.2 =
* Improve English text thanks to Rene
* Correct a bug since home_url is different from site_url (thanks to Julian)

= 1.2.1 =
* Update of the core and translations

= 1.2.0 =
* SVN support

= 1.1.3 =
* Updating the core plugin

= 1.1.2 =
* Replacing the word "force" into "edit" (trevor's suggestion)
* When forcing an url, you may use a-zA-Z0-9.-_ characters (trevor's suggestion)
* ZipArchive class has been suppressed and pclzip is used instead

= 1.1.1 =
* Ensure that folders and files permissions are correct for an adequate behavior

= 1.1.0 =
* You can add any shortlink you want (i.e. with external links)
* Add translation for French

= 1.0.2 =
* Upgrade of the framework (version 3.0)

= 1.0.1 =
* First release in the wild web (enjoy)

== Frequently Asked Questions ==

* Where can I read more?

Visit http://www.sedlex.fr/cote_geek/

 
InfoVersion:37baa22a62d3f2705da90e4f3f55b327