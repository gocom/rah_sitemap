<?php

/**
 * This is an example plugin for rah_sitemap. Snowcases extending.
 * 
 * @package rah_sitemap
 * @author Jukka Svahn <http://rahforum.biz/>
 * @copyright (c) 2012 Jukka Svahn
 * @license GLPv2
 *
 * The plugin adds two new urls to the XML sitemap using
 * rah_sitemap's methods rah_sitemap::get() and rah_sitemap::url().
 */

/**
 * Registers abc_sitemap_urlset() function to rah_sitemap.urlset event
 */

	register_callback('abc_sitemap_urlset', 'rah_sitemap.urlset');

/**
 * Adds two new URLs to the sitemap
 */

	function abc_sitemap_urlset() {
		rah_sitemap::get()
			->url('/some/local/url')
			->url('/some/second/url', '2011-02-13 16:07:25');
	}

?>