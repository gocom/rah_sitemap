<?php

/**
 * Links module for rah_sitemap.
 * 
 * @package   rah_sitemap
 * @author    Jukka Svahn <http://rahforum.biz/>
 * @copyright (c) 2012 Jukka Svahn
 * @license   GLPv2
 */

	new rah_sitemap__links();

/**
 * The module class.
 */

class rah_sitemap__links {

	/**
	 * Constructor.
	 */

	public function __construct() {
		register_callback(array($this, 'urlset'), 'rah_sitemap.urlset');
	}

	/**
	 * Adds links to the sitemap.
	 */

	public function urlset() {
		$local = str_replace(array('%', '_'), array('\\%', '\\_'), doSlash(hu));

		$rs = 
			safe_rows(
				'url, date',
				'txp_link',
				"category='rah_sitemap' or url LIKE '".$local."_%' or url LIKE '/_%'"
			);

		foreach($rs as $a) {
			rah_sitemap::get()->url($a['url'], $a['date']);
		}
	}
}

?>