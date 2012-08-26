<?php

/**
 * Rah_sitemap plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_sitemap
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	rah_sitemap::get();

class rah_sitemap {

	static public $version = '1.2';
	
	/**
	 * @var obj Stores instances
	 */
	
	static public $instance = NULL;
	
	/**
	 * @var array Stores XML urlset
	 */
	
	public $urlset = array();
	
	/**
	 * @var array Stores allowed article fields
	 */
	
	protected $article_fields = array();

	/**
	 * Installer
	 * @param string $event Admin-side callback event.
	 * @param string $step Admin-side plugin-lifecycle step.
	 */

	static public function install($event='', $step='') {
		
		global $prefs;
		
		if($step == 'deleted') {
			
			safe_delete(
				'txp_prefs',
				"name like 'rah\_sitemap\_%'"
			);
			
			return;
		}
		
		if((string) get_pref(__CLASS__.'_version') === self::$version) {
			return;
		}
		
		$opt = array(
			'exclude_categories' => array('rah_sitemap_categories', array()),
			'exclude_sections' => array('rah_sitemap_sections', array()),
			'exclude_fields' => array('rah_sitemap_textarea', array()),
			'urls' => array('rah_sitemap_textarea', ''),
			'future_articles' => array('yesnoradio', 0),
			'past_articles' => array('yesnoradio', 1),
			'expired_articles' => array('yesnoradio', 1),
			'exclude_sticky_articles' => array('yesnoradio', 1),
		);
		
		@$rs = safe_rows('name, value', 'rah_sitemap_prefs', '1=1');
		
		if($rs) {
			foreach($rs as $a) {
				
				if(trim($a['value']) === '') {
					continue;
				}
			
				if($a['name'] == 'articlecategories') {
					foreach(do_list($a['value']) as $v) {
						$opt['exclude_fields'][1][] = 'Category1: ' . $v;
						$opt['exclude_fields'][1][] = 'Category2: ' . $v;
					}
				}
				
				elseif($a['name'] == 'articlesections') {
					foreach(do_list($a['value']) as $v) {
						$opt['exclude_fields'][1][] = 'Section: ' . $v;
					}
				}
				
				elseif($a['name'] == 'sections') {
					$opt['exclude_sections'][1] = do_list($a['value']);
				}
				
				elseif($a['name'] == 'categories' && strpos($a['value'], 'article_||_') !== false) {
					foreach(do_list($a['value']) as $v) {
						if(strpos($v, 'article_||_') === 0) {
							$opt['exclude_categories'][1][] = substr($v, 11);
						}
					}
				}
				
				elseif(isset($opt[$a['name']])) {
					$opt[$a['name']][1] = $a['value'];
				}
			}
			
			@$rs = safe_column('url', 'rah_sitemap', '1=1');
			
			if($rs) {
				$opt['urls'][1] = implode(', ', $rs);
			}
			
			@safe_query('DROP TABLE IF EXISTS '.safe_pfx('rah_sitemap'));
			@safe_query('DROP TABLE IF EXISTS '.safe_pfx('rah_sitemap_prefs'));
		}
		
		$position = 260;
		
		foreach($opt as $name => $val) {
			$n = __CLASS__.'_'.$name;
			
			if(!isset($prefs[$n])) {
				
				if(is_array($val[1])) {
					$val[1] = implode(',', $val[1]);
				}
				
				set_pref($n, $val[1], __CLASS__, PREF_ADVANCED, $val[0], $position);
				$prefs[$n] = $val[1];
			}
			
			$position++;
		}
		
		set_pref(__CLASS__.'_version', self::$version, __CLASS__, 2, '', 0);
		$prefs[__CLASS__.'_version'] = self::$version;
	}

	/**
	 * Constructor
	 */

	public function __construct() {
		add_privs('plugin_prefs.'.__CLASS__, '1,2');
		register_callback(array(__CLASS__, 'install'), 'plugin_lifecycle.'.__CLASS__);
		register_callback(array($this, 'prefs'), 'plugin_prefs.'.__CLASS__);
		register_callback(array($this, 'prefs_save'), 'prefs', 'advanced_prefs_save', 1);
		register_callback(array($this, 'page_handler'), 'textpattern');
	}

	/**
	 * Gets an instance of the class
	 * @return obj
	 */
	
	static public function get() {
		
		if(self::$instance === NULL) {
			self::$instance = new rah_sitemap();
		}
		
		return self::$instance;
	}
	
	/**
	 * Handles preference saving
	 */
	
	public function prefs_save() {
	
		if(empty($_POST) || !is_array($_POST)) {
			return;
		}
		
		foreach(array('exclude_sections', 'exclude_categories') as $name) {
		
			$name = 'rah_sitemap_' . $name;
		
			if(isset($_POST[$name]) && is_array($_POST[$name])) {
				$_POST[$name] = implode(', ', $_POST[$name]);
			}
			
			else {
				$_POST[$name] = '';
			}
		}
	}
	
	/**
	 * Handles returning the sitemap
	 */
	
	public function page_handler() {
		
		global $pretext;
		
		if(!gps('rah_sitemap') && strpos(end(explode('/', $pretext['request_uri'])), 'sitemap.xml') !== 0) {
			return;
		}
		
		return $this->populate_article_fields()->get_sitemap();
	}

	/**
	 * Generates and outputs the sitemap
	 */

	protected function get_sitemap() {
		
		global $prefs;
		
		$this->url(hu);
		
		$s = array_merge(array("'default'"), quote_list(do_list($prefs['rah_sitemap_exclude_sections'])));
		
		$rs = 
			safe_rows(
				'name',
				'txp_section',
				'name NOT IN('.implode(',', $s).') ORDER BY name ASC'
			);
		
		foreach($rs as $a) {
			$this->url(pagelinkurl(array('s' => $a['name'])));
		}
		
		$c = array_merge(array("'root'"), quote_list(do_list($prefs['rah_sitemap_exclude_categories'])));
		
		$rs = 
			safe_rows(
				'name',
				'txp_category',
				"name NOT IN(".implode(',', $c).") AND type='article' ORDER BY name asc"
			);
		
		foreach($rs as $a) {
			$this->url(pagelinkurl(array('c' => $a['name'])));
		}
		
		$sql = array('Status >= 4');
		
		foreach(do_list($prefs['rah_sitemap_exclude_fields']) as $field) {
			if($field) {
				$f = explode(':', $field);
				$n = strtolower(trim($f[0]));

				if(isset($this->article_fields[$n])) {
					$sql[] = $this->article_fields[$n]." NOT LIKE '".doSlash(trim(implode(':', array_slice($f, 1))))."'";
				}
			}
		}
		
		if($prefs['rah_sitemap_exclude_sticky_articles']) {
			$sql[] = 'Status != 5';
		}
		
		if(!$prefs['rah_sitemap_future_articles']) {
			$sql[] = 'Posted <= now()';
		}
		
		if(!$prefs['rah_sitemap_past_articles']) {
			$sql[] = 'Posted >= now()';
		}
		
		if(!$prefs['rah_sitemap_expired_articles']) {
			$sql[] = "(Expires = ".NULLDATETIME." or Expires >= now())";
		}
		
		$rs = 
			safe_rows(
				'*, unix_timestamp(Posted) as uPosted, unix_timestamp(LastMod) as uLastMod',
				'textpattern',
				implode(' and ', $sql) . ' ORDER BY Posted DESC'
			);
		
		foreach($rs as $a) {
			$this->url(permlinkurl($a), (int) max($a['uLastMod'], $a['uPosted']));
		}
		
		foreach(do_list($prefs['rah_sitemap_urls']) as $url) {
			if($url) {
				$this->url($url);
			}
		}
		
		callback_event('rah_sitemap.urlset');
		
		$xml = 
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
			implode('', $this->urlset).
			'</urlset>';
		
		ob_clean();
		txp_status_header('200 OK');
		header('Content-type: application/xml; charset=UTF-8');
		
		if(
			strpos(serverSet('HTTP_ACCEPT_ENCODING'), 'gzip') !== false && 
			@extension_loaded('zlib') && 
			@ini_get('zlib.output_compression') == 0 && 
			@ini_get('output_handler') != 'ob_gzhandler' &&
			!@headers_sent()
		) {
			header('Content-Encoding: gzip');
			$xml = gzencode($xml);
		}
		
		echo $xml;
		exit;
	}
	
	/**
	 * Generates XML sitemap url item
	 * @param string $url
	 * @param int|string $lastmod
	 * @return obj
	 */
	
	public function url($url, $lastmod=NULL) {
	
		if(strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
			$url = hu . ltrim(htmlspecialchars($url), '/');
		}
		
		if(isset($this->urlset[$url])) {
			return $this;
		}
		
		if($lastmod !== NULL) {
		
			if(is_string($lastmod)) {
				$lastmod = strtotime($lastmod);
			}
			
			if($lastmod !== false) {
				$lastmod = date('c', $lastmod);
			}
		}
		
		$this->urlset[$url] = 
			'<url>'.
				'<loc>'.$url.'</loc>'.
				($lastmod ? '<lastmod>'.$lastmod.'</lastmod>' : '').
			'</url>';
		
		return $this;
	}

	/**
	 * Populates allowed article fields
	 * @return obj
	 */

	protected function populate_article_fields() {
	
		$columns = (array) @getThings('describe '.safe_pfx('textpattern'));
		
		foreach($columns as $name) {
			$this->article_fields[strtolower($name)] = $name;
		}
		
		foreach(getCustomFields() as $id => $name) {
			$this->article_fields[$name] = 'custom_'.intval($id);
		}
		
		return $this;
	}

	/**
	 * Options page
	 */

	public function prefs() {
		echo 
			'<p>'.n.
			'	<a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_sitemap_exclude_categories">'.gTxt('rah_sitemap_view_prefs').'</a><br />'.n.
			'	<a href="'.hu.'?rah_sitemap=sitemap">'.gTxt('rah_sitemap_view_sitemap').'</a>'.
			'</p>';
	}
	
	/**
	 * Returns a multi-select option
	 * @param string $name
	 * @param array $values
	 * @param string|array $selected
	 * @return HTML markup
	 */
	
	static public function multiselect($name, $values, $selected) {
		
		if(!is_array($selected)) {
			$selected = do_list($selected);
		}
		
		$name = htmlspecialchars($name);
		$out = array();
		$i = 0;
		
		foreach($values as $value => $label) {
			$id = $name.($i++);
			$out[] = '<input type="checkbox" id="'.$id.'" name="'.$name.'[]" value="'.htmlspecialchars($value).'"'.(in_array($value, $selected) ? ' checked="checked"' : '').' /> <label for="'.$id.'">'.htmlspecialchars($label).'</label>';
		}
		
		return implode('<br />', $out);
	}
}

/**
 * Lists all available sections
 * @param string $name
 * @param string $value
 * @return string HTML
 */

	function rah_sitemap_sections($name, $value) {
		
		$rs = 
			safe_rows(
				'name, title',
				'txp_section',
				"name != 'default' ORDER BY title asc"
			);
		
		$out = array();
		
		foreach($rs as $a) {
			$out[$a['name']] = $a['title'];
		}
		
		return rah_sitemap::multiselect($name, $out, $value);
	}

/**
 * Lists all available categories
 * @param string $name
 * @param string $value
 * @return string HTML
 */

	function rah_sitemap_categories($name, $value) {
		
		$rs = 
			safe_rows(
				'name, title',
				'txp_category',
				"type = 'article' AND name != 'root' ORDER BY title asc"
			);
		
		$out = array();
		
		foreach($rs as $a) {
			$out[$a['name']] = $a['title'];
		}
		
		return rah_sitemap::multiselect($name, $out, $value);
	}

/**
 * Lists all excluded article fields
 * @param string $name
 * @param string $value
 * @return string HTML textarea
 */

	function rah_sitemap_textarea($name, $value) {
		return text_area($name, 60, 300, $value, $name);
	}

?>