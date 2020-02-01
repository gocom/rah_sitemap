# Changelog

## Version 2.1.0 - 2020/01/01

* Added: Global options to exclude categories from the sitemap by type. Thank you, [Sebastian Spautz](https://github.com/sebastiansIT).
* Added: German translation. Thank you, [Sebastian Spautz](https://github.com/sebastiansIT).

## Version 2.0.2 - 2020/01/01

* Fixed: yes-no toggle default-selection rendering. Thank you, [Sebastian Spautz](https://github.com/sebastiansIT).
* Fixed: expired article exclusion. Thank you, [Sebastian Spautz](https://github.com/sebastiansIT).

## Version 2.0.1 - 2019/11/01

* Fixed: option to exclude expired articles.

## Version 2.0.0 - 2019/04/20

* Fixed: Generates valid `/year/month/day/title` permanent links. Thank you, [Wladimir Palant](https://github.com/palant).
* Drop old legacy preference migration code.
* Use preference API to create preference options.
* Now requires Textpattern 4.7.0 or newer.
* Now requires PHP 7.2.0 or newer.

## Version 1.3.0 - 2014/03/28

* Changed: Integrated preferences to Textpattern's native preferences panel, and to Section and Category editors.
* Added: Language strings, interface is now translatable using Textpacks.
* Added: Finnish translation.
* Added: French translation by [Patrick Lefevre](https://twitter.com/lowel).
* Added: Chinese translation by [WizJin](https://github.com/wizjin).
* Improved: Compatibility with Textpattern 4.5.0.
* Now requires Textpattern 4.5.0 or newer.

## Version 1.2 - 2011/03/09

* Added: adds site URL to relative article permlinks. Basically a fix for gbp_permanent_links.
* Changed: from `permlinkurl_id()` to `permlinkurl(). Greatly reduced the amount of queries generating article permlinks makes.

## Version 1.1 - 2010/10/30

* Fixed issues appearing with the installer when MySQL is in strict mode. [Thank you for reporting, Gallex](http://forum.textpattern.com/viewtopic.php?pid=236637#p236637).

## Version 1.0 - 2010/10/29

* Slightly changed backend's installer call; only check for installing if there is no preferences available.

## Version 0.9 - 2010/08/25

* Fixed: now correctly parses category tags in category URLs. Thank you for [reporting](http://forum.textpattern.com/viewtopic.php?pid=233619#p233619), Andreas.

## Version 0.8 - 2010/07/27

* Now compression level field's label now links to the correct field id.
* Now suppresses E_WARNING/E_STRICT notices in live mode caused by Textpattern's timezone code when some conditions are met (TXP 4.2.0, PHP 5.1.0+, TXP's Auto-DST feature disabled, TXP in Live mode). Error suppression will be removed when TXP version is released with fully working timezone settings.
* Now generates UNIX timestamps within the SQL query, not with PHP.
* Changed sliding panels' links (`a` elements) into spans.

## Version 0.7 - 2010/05/30

* Fixed: now deleting custom url leads back to the list view, not to the editing form.
* Removed some leftover inline styles from v0.6.

## Version 0.6 - 2010/05/30

* Rewritten the code that generates the sitemap.
* New admin panel look.
* Now custom permlink modes and custom urls are escaped. Users can input unescaped URLs/markup from now on.
* Now custom URL list shows the full formatted URL after auto-fill instead of the user input.
* Now custom URLs that start with www. are completed with http:// protocol.
* Now all urls that do not start with either http, https, www, ftp or ftps protocol are auto-completed with the site's address.
* Custom url editor got own panel. No longer the form is above the URL list.
* Added ability to manually turn gzib compression off and change the compression level.
* Added setting to set zlib.output_compression off. [See here](http://forum.textpattern.com/viewtopic.php?pid=224931#p224931), thank you for reporting superfly.
* Preferences are now trimmed during save.
* Merged `rah_sitemap_update()` with `rah_sitemap_save()`.
* From now on all new installations have default settings defined that will automatically exclude link, file and image categories from the sitemap. This won't effect updaters.
* Changed sitemap's callback register from pre `pretext` to callback after it (callback is now `textpattern`). Now `$pretext` is set before the sitemap and thus more plugins might work within permlink settings and custom urls.
* When using Textpattern's clean URLs, requesting `/sitemap.xml.gz` and `/sitemap.xml` URLs will return the sitemap, not just the `/?rah_sitemap=sitemap`. This will of course require existing fully working clean urls.

## Version 0.5 - 2010/03/01

* Added customizable timestamp formats.
* Cleaned backend markup.
* Combined individual preference queries.

## Version 0.4 - 2009/04/12

* Added support for custom permlink rules: Now you can easily set any kind of permlink rules for articles, section and categories.
* Added option to exclude future articles.
* Added option to exclude past articles.
* Added option to exclude expired articles.
* Moved Custom URL UI to it's own page.
* Added multi-delete feature to Custom URL UI.
* Improved Custom URL UI.
* Removed default static appending domain from Custom URL input field.
* Changed TXP minimum requirement to version 4.0.7 (and above). Note that the plugin still works with older TXP versions (down to 4.0.5) if the _Exclude Expired articles_ -option is left empty (unset).

## Version 0.3.2 - 2008/10/25

* Fixed view url that still (from version 0.2) included installation address before link.

## Version 0.3 - 2008/10/24

* Added option to insert URLs that are outside Textpattern install directory.
* Fixed option to exclude categories directly by type: added forgotten link type.

## Version 0.2 - 2008/10/22

* Added option to exclude/include sticky articles.
* Added option to exclude categories directly by type.
* Fixed bug: now shows all categories, and not only article-type, in admin panel.
* Fixed bug: removed double install query (didn't do a thing, just checked table status twice).

## Version 0.1.2 - 2008/09/12

* Fixed article listing bug caused by nasty little typo: now only 4 and 5 statuses are listed.

## Version 0.1 - 2008/09/07

* First release.
