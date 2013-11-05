=== Fraxion Payments Micropayments ===
Contributors:  GarryCL, DanDare2050, Chris Wilkins
Tags: micropayments, online journalism, citizen journalism, paid content, bloggers, blogging, blog, online magazine, ezine, news, newspaper, digital news, books, short stories, online articles
Requires at least: 3.0.0
Tested up to: 3.6.0
Stable tag: 2.0.0

"Micropayments for bloggers". Sell articles, short stories and attached files for as little as 1c. You set the price. Readers "unlock" with 1 click.

== Description ==

This simple plug-in immediately makes "micropayments for content" a reality. Set up is simple, and with only a few steps you can lock your articles and stories. You set your own prices for your content, as little as 1c.

Readers can unlock these articles with one-click, exchanging a small number of cents for quality content.

This makes it simple for authors to charge for content in a way that is easy, convenient and priced fairly for their readers.

Locked articles can also appear in the Fraxion Payments catalogue with new features for user rating and searching on the way.

A great plug-in for journalists, bloggers and writers who want to turn their creativity into an income.

Version 2 makes it easy to also sell videos, music and pdfs.


== Installation ==

1. Unzip the downloaded file and find the folder called fraxion.
2. Upload the fraxion folder to your /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to "settings" and then "Fraxion" to register you site.

For help on how to use go to http://www.fraxionpayments.com/creators/

== Frequently Asked Questions ==

= What are micropayments? =

Micropayments are very small amounts you pay for online content. By actually paying for this content the quality is naturally going to be better than free content.

Micropayments form one part of the ongoing debate of "free" vs "paid" content, made prominent by people such as Rupert Murdoch and newspapers like the New York Times.

= Where does this technology come from? =

The core technology has successfully been in operation since 2004 at www.towergames.com and was migrated to Fraxion Payments in 2010.

= What about "free" content? =

We don't believe in it. What we believe in is "fair exchange". http://www.fraxionpayments.com/2010/02/blog/paidcontent/fair-exchange/.

= Will this help online journalism? =

It can only help. With 31,000 journalist laid off in the USA alone over the last few years this is now a great way for those same people to get back to work, but this time for themselves doing what they know best. That is, using their contacts to discover and create great stories.

== Screenshots ==

1. A locked article
2. The writer's / editor's screen
3. Setting the price of a locked article

== Changelog ==

= 2.0.0 =
New function: locked attachments. Allows files to be attached to an article. PDF, mp3, mp4, txt, jpg anything. There are two versions uploaded for each file, a snippet and a full version. When selecting the link the full version is provided only if you are logged in and have unlocked the associated article. Otherwise the snippet is delivered.
There are also significant changes to the layout of the banners in articles.

= 1.3.8 =
Important Adjustments for Wordpress 3.3.1 Fraxion button was not appearing in the HTML editor.

= 1.3.7 =
Fixed bug that closes comments on posts that are not tagged.

= 1.3.6 =
Fixed a bug that stopped page production if the Fraxion Payments server was not responding.
A clean "unavailable" banner is now produced at the lock position.
Tidied up the setting of window.onload so it plays nice with other scripts that have set it previously.

= 1.3.5 =
Path Separators defined for Windows server install.
Comment hiding script at the foot of the page instead of using window.onload() to avoid clashes with other plugins.

= 1.3.4 =
Repair word press 3.1 problem with JQuery for post info dialog.
Simultaneous release with the new royalty payment system.
Allows people other than the site owner to receive payment for sales, as assigned by the site admin.

= 1.3.3 =
Minor fix to internal directory navigation where subdomain is in use.
Minor fix for compatabilit with Word Press version 3.1

= 1.3.2 =
Adds the automatic catalogue system for locked documents.
Fixes a bug that would allow the content of locked documents to be accessed by web bots.
More robust handling of content when splicing in the banner.

= 1.3.1 = do not use

= 1.2.0 =
New look and feel for the banners improving usability, including more robust CSS.
Fixed a bug to do with registering a site running in a sub domain.

= 1.1.3 =
Fixed CSS for banners to prevent banner from being disrupted by themes.
Repaired character translation for various characters.
Minor stability fixes.

= 1.1.0 =
New Functions:
 View Account, Catalogue link, logout, footer for unlocked documents, improved information for first time readers.
Fixes:
 RSS Feed handling, output for certain common robots such as xml-sitemap and tweetmeme, visual display protection and general robustness.

= 1.0.2 =
Fixed a bug that interfered with content display using I.E., Safari and Chrome on Windows OS with some themes.
Removed a javascript function that was being interfered with by other plugins and replaced it with markup produced by the server
when the page is generated.

= 1.0.1 =
Changed some class functions from private to public to allow proper admin call back. This interfered with some admin functions.

= 1.0.0 =

This new version of the plugin no longer requires readers of articles to be logged in to word press to do an unlock. That means readers only have to login to Fraxion Payments once and may move from web site to web site unlocking articles as they wish.

= 0.5.5 =

Fixed bug with links to online test version of Fraxion Payments.

= 0.5.4 =

Resolved conflicts with some themes.
Added a "Register" button to register to the wordpress site when not logged in to the site.
Added progressive user help to lead new readers through the process.
Admins may see more sales history at fraxion site.
Comming soon
Remove the need for readers to be logged in at the word press site
Site Admin may view accounts - income, margin and balance, for their sites

Bug: Links to test site of fraxion payments cause problems. Switch to version 0.5.5

= 0.5.2 =

Wordpress MU compatible. Tracks which sites belong to a MU network and which is the base site.
Adds information to the lock banners to help readers know what is involved with unlocking an article.
Slightly more signposting around admin functions.
Fixed a bug that caused the titles of articles to be null in the Fraxion Payments database.

= 0.4.9 =

Fix bug with pretty links introduced with 0.4.8.

= 0.4.8 =

Fix one more bug with pretty links where relative URLs get the wrong path.

= 0.4.7 =

Fixed bug with pretty links that caused banner and login problems.
Fixed the "Fraxion" button, bug prevented it from inserting the lock tag intermittently.

= 0.4.6 =

Repair some script bugs that interfered with wordpress login.

= 0.4.4 =

Remove faulty admin button. Replace later.

= 0.4.3 =

The first release. We think most bugs have been solved. But of course please let us know if you find any.

== Upgrade Notice ==

= 2.0.0 =
Important New function: locked attachments. Allows files to be attached to an article. PDF, mp3, mp4, txt, jpg anything. There are two versions uploaded for each file, a snippet and a full version. When selecting the link the full version is provided only if you are logged in and have unlocked the associated article. Otherwise the snippet is delivered.
There are also significant changes to the layout of the banners in articles.

= 1.3.8 =
Important Adjustments for Wordpress 3.3.1 If you have Wordpress 1.3.1 you need this plugin update.

= 1.3.6 =
Fixed a bug that stopped page production if the Fraxion Payments server was not responding.
A clean "unavailable" banner is now produced at the lock position. Internal links from the administration
end also behave more respectfully.
Tidied up the setting of window.onload so it plays nice with other scripts that have set it previously.

= 1.3.5 =
This version of the plugin is required if you are running you Wordpress on a windows server.
Some of the page script functions have been changed to avoid clashes with other plugins and themes.

= 1.3.4 =
Repair word press 3.1 problem with JQuery for post info dialog.
Simultaneous release with the new royalty payment system.
Allows people other than the site owner to receive payment for sales, as assigned by the site admin.

= 1.3.3 =
Minor fix to internal directory navigation where subdomain is in use.
Minor fix for compatabilit with Word Press version 3.1

= 1.3.2 =
Necessary upgrade. Older versions will not be able to change the lock status and fraxion cost for each article.
Adds the automatic catalogue system for locked documents.
Fixes a bug that would allow the content of locked documents to be accessed by web bots.
More robust handling of content when splicing in the banner.

= 1.3.1 = do not use

= 1.2.0 =
New look and feel for the banners improving usability, including more robust CSS. This is a must have upgrade.
Fixed a bug to do with registering a site running in a sub domain.

= 1.1.3 =
CSS improvements for the banners and fixes to translation of special characters as well as all the new functionality of 1.1.0, new functions to enhance user friendliness including View Account, Catalogue link, logout, footer for unlocked documents (with future use for rating documents), and improved information for first time readers. Problems fixed with RSS Feed handling, output for certain common robots such as xml-sitemap and tweetmeme.

= 1.1.0 =
Several new functions to enhance user friendliness including View Account, Catalogue link, logout, footer for unlocked documents (with future use for rating documents), and improved information for first time readers.

Some problems fixed with RSS Feed handling, output for certain common robots such as xml-sitemap and tweetmeme. Also visual display protection and general robustness.

= 1.0.2 =
If your locked articles display only headings in some circumstances or the links to unlock are not working you need this version.
Fixed a bug that interfered with content display using I.E., Safari and Chrome on Windows OS with some themes.
Removed a javascript function that was being interfered with by other plugins and replaced it with markup produced by the server
when the page is generated.

= 1.0.1 =

Fixed a bug that would prevent admin of posts and fraxion payments settings.

= 1.0.0 =

This new version of the plugin no longer requires readers of articles to be logged in to word press to do an unlock. That means readers only have to login to Fraxion Payments once and may move from web site to web site unlocking articles as they wish.

= 0.5.5 =

Fixed bug with links to online test version of Fraxion Payments. If you are using 0.5.4 upgrade immediately.

= 0.5.4 =

Resolved conflicts with some themes.
Added a "Register" button to register to the wordpress site when not logged in to the site.
Added progressive user help to lead new readers through the process.

= 0.5.2 =

Increases take up by readers by adding information to the lock banners to help readers know what is involved with unlocking an article.
Slightly more signposting
Fixed a bug that caused the titles of articles to be null in the Fraxion Payments database.
Wordpress MU compatible allowing Fraxion Payments to know which is a MU base site and which are other sites in its network.

= 0.4.9 =

Fixes bug with pretty links introduced with 0.4.8. Upgrade essential if you use the permalink system.

= 0.4.8 =

Fixes one more bug with pretty links where relative URLs get the wrong path.

= 0.4.7 =

Fixes a bug with pretty links that caused banner and login problems.
Fixes the "Fraxion" button, an intermittent bug prevented it from inserting the lock tag when pressed.

= 0.4.6 =

Removes a bug that could put off users.

= 0.4.4 =

Removed Bug in Settings page that could lose connection of your site to Fraxion.

= 0.4.3 =

Nothing to upgrade to at the moment.

== Working Sites ==

These are some sites that use the Fraxion Payments plug-in. Check them out if you want to see the plug-in in action.

1. http://www.kwikreads.com	- Short stories by various authors
2. http://www.casualravings.com - Chris Wilkins' blog
3. http://www.swordandrifle.com - Military history magazine associated with http://www.towergames.com
4. http://www.battleaxebooks.com/handar/ - A complete sword and sorcery novel with individual chapters locked

Go to the fraxion payments catalogue for an up to date list: http://www.fraxionpayments.com/catalogue