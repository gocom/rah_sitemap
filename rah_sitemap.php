<?php

/**
 * Rah_sitemap plugin for Textpattern CMS.
 *
 * @author  Jukka Svahn
 * @date    2008-
 * @license GNU GPLv2
 * @link    http://rahforum.biz/plugins/rah_sitemap
 *
 * Copyright (C) 2012 Jukka Svahn http://rahforum.biz
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	rah_sitemap::get();

/**
 * The plugin class.
 */

class rah_sitemap
{
	/**
	 * Version number.
	 *
	 * @var string
	 */

	static public $version = '1.2';

	/**
	 * Stores instances.
	 *
	 * @var obj
	 */

	static public $instance = NULL;

	/**
	 * Stores an XML urlset.
	 *
	 * @var array
	 */

	public $urlset = array();

	/**
	 * Stores an array of mapped article fields.
	 *
	 * @var array
	 */

	protected $article_fields = array();

	/**
	 * Installer.
	 *
	 * @param string $event Admin-side plugin-lifecycle event
	 * @param string $step  Admin-side plugin-lifecycle step
	 */

	static public function install($event = '', $step = '')
	{
		global $prefs;

		if ($step == 'deleted')
		{
			safe_delete(
				'txp_prefs',
				"name like 'rah\_sitemap\_%'"
			);

			safe_alter(
				'txp_section',
				'DROP COLUMN rah_sitemap_include_in'
			);

			safe_alter(
				'txp_category',
				'DROP COLUMN rah_sitemap_include_in'
			);

			return;
		}

		if ((string) get_pref(__CLASS__.'_version') === self::$version)
		{
			return;
		}

		$opt = array(
			'exclude_fields' => array('pref_longtext_input', array()),
			'urls' => array('pref_longtext_input', ''),
			'future_articles' => array('yesnoradio', 0),
			'past_articles' => array('yesnoradio', 1),
			'expired_articles' => array('yesnoradio', 1),
			'exclude_sticky_articles' => array('yesnoradio', 1),
		);

		if (!in_array('rah_sitemap_include_in', getThings('describe '.safe_pfx('txp_section'))))
		{
			safe_alter('txp_section', 'ADD rah_sitemap_include_in TINYINT(1) NOT NULL DEFAULT 1');
		}

		if (!in_array('rah_sitemap_include_in', getThings('describe '.safe_pfx('txp_category'))))
		{
			safe_alter('txp_category', 'ADD rah_sitemap_include_in TINYINT(1) NOT NULL DEFAULT 1');
		}

		if (in_array(PFX.'rah_sitemap_prefs', getThings('SHOW TABLES')))
		{
			$update = array(
				'sections' => array(),
				'categories' => array(),
			);

			$rs = safe_rows('name, value', 'rah_sitemap_prefs', '1=1');

			foreach($rs as $a)
			{
				if (trim($a['value']) === '')
				{
					continue;
				}

				if ($a['name'] == 'articlecategories')
				{
					foreach (do_list($a['value']) as $v)
					{
						$opt['exclude_fields'][1][] = 'Category1: ' . $v;
						$opt['exclude_fields'][1][] = 'Category2: ' . $v;
					}
				}

				else if ($a['name'] == 'articlesections')
				{
					foreach (do_list($a['value']) as $v)
					{
						$opt['exclude_fields'][1][] = 'Section: ' . $v;
					}
				}

				elseif ($a['name'] == 'sections')
				{
					$update['sections'] = do_list($a['value']);
				}

				else if ($a['name'] == 'categories')
				{
					foreach (do_list($a['value']) as $v)
					{
						$v = explode('_||_', $v);
						$update['categories'][$v[0]][] = end($v);
					}
				}

				else if (isset($opt[$a['name']]))
				{
					$opt[$a['name']][1] = $a['value'];
				}
			}

			@$rs = safe_column('url', 'rah_sitemap', '1=1');

			if ($rs)
			{
				$opt['urls'][1] = implode(', ', $rs);
			}

			if ($update['categories'])
			{
				foreach ($update['categories'] as $type => $categories)
				{
					safe_update('txp_category', 'rah_sitemap_include_in=0', "type='".doSlash($type)."' and name IN(".implode(',', quote_list($categories)).")");
				}
			}

			if ($update['sections'])
			{
				safe_update('txp_section', 'rah_sitemap_include_in=0', 'name IN('.implode(',', quote_list($update['sections'])).')');
			}

			@safe_query('DROP TABLE IF EXISTS '.safe_pfx('rah_sitemap'));
			@safe_query('DROP TABLE IF EXISTS '.safe_pfx('rah_sitemap_prefs'));
		}

		$position = 260;

		foreach ($opt as $name => $val)
		{
			$n = __CLASS__.'_'.$name;
			
			if (!isset($prefs[$n]))
			{
				if (is_array($val[1]))
				{
					$val[1] = implode(',', $val[1]);
				}

				set_pref($n, $val[1], __CLASS__, PREF_ADVANCED, $val[0], $position);
			}

			$position++;
		}

		set_pref(__CLASS__.'_version', self::$version, __CLASS__, PREF_HIDDEN);
	}

	/**
	 * Constructor.
	 */

	public function __construct()
	{
		add_privs('plugin_prefs.'.__CLASS__, '1,2');
		add_privs('prefs.'.__CLASS__, '1,2');
		register_callback(array(__CLASS__, 'install'), 'plugin_lifecycle.'.__CLASS__);
		register_callback(array($this, 'prefs'), 'plugin_prefs.'.__CLASS__);
		register_callback(array($this, 'page_handler'), 'textpattern');
		register_callback(array($this, 'section_ui'), 'section_ui', 'extend_detail_form');
		register_callback(array($this, 'category_ui'), 'category_ui', 'extend_detail_form');
		register_callback(array($this, 'section_save'), 'section', 'section_save');
		register_callback(array($this, 'category_save'), 'category', 'cat_article_save');
		register_callback(array($this, 'category_save'), 'category', 'cat_image_save');
		register_callback(array($this, 'category_save'), 'category', 'cat_file_save');
		register_callback(array($this, 'category_save'), 'category', 'cat_link_save');
	}

	/**
	 * Gets an instance of the class.
	 *
	 * @return rah_sitemap
	 */

	static public function get() {

		if (self::$instance === NULL)
		{
			self::$instance = new rah_sitemap();
		}

		return self::$instance;
	}

	/**
	 * Handles returning the sitemap.
	 */

	public function page_handler()
	{
		global $pretext;

		if (!gps('rah_sitemap') && basename($pretext['request_uri'], '.gz') !== 'sitemap.xml')
		{
			return;
		}

		return $this->populate_article_fields()->get_sitemap();
	}

	/**
	 * Generates and outputs the sitemap.
	 */

	protected function get_sitemap()
	{
		global $prefs;

		$this->url(hu);

		$rs = 
			safe_rows_start(
				'name',
				'txp_section',
				"name != 'default' and rah_sitemap_include_in=1 ORDER BY name ASC"
			);

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				$this->url(pagelinkurl(array('s' => $a['name'])));
			}
		}

		$rs = 
			safe_rows_start(
				'name, type',
				'txp_category',
				"name != 'root' and rah_sitemap_include_in=1 ORDER BY name asc"
			);

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				$this->url(pagelinkurl(array('c' => $a['name'], 'context' => $a['type'])));
			}
		}

		$sql = array('Status >= 4');

		foreach (do_list($prefs['rah_sitemap_exclude_fields']) as $field)
		{
			if ($field)
			{
				$f = explode(':', $field);
				$n = strtolower(trim($f[0]));

				if (isset($this->article_fields[$n]))
				{
					$sql[] = $this->article_fields[$n]." NOT LIKE '".doSlash(trim(implode(':', array_slice($f, 1))))."'";
				}
			}
		}

		if ($prefs['rah_sitemap_exclude_sticky_articles'])
		{
			$sql[] = 'Status != 5';
		}

		if (!$prefs['rah_sitemap_future_articles'])
		{
			$sql[] = 'Posted <= now()';
		}

		if (!$prefs['rah_sitemap_past_articles'])
		{
			$sql[] = 'Posted >= now()';
		}
		
		if (!$prefs['rah_sitemap_expired_articles'])
		{
			$sql[] = "(Expires = ".NULLDATETIME." or Expires >= now())";
		}

		$rs = 
			safe_rows_start(
				'*, unix_timestamp(Posted) as uPosted, unix_timestamp(LastMod) as uLastMod',
				'textpattern',
				implode(' and ', $sql) . ' ORDER BY Posted DESC'
			);

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				$this->url(permlinkurl($a), (int) max($a['uLastMod'], $a['uPosted']));
			}
		}

		foreach (do_list($prefs['rah_sitemap_urls']) as $url)
		{
			if ($url) {
				$this->url($url);
			}
		}

		callback_event('rah_sitemap.urlset');

		$xml = 
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
			implode('', array_slice($this->urlset, 0, 50000)).
			'</urlset>';

		ob_clean();
		txp_status_header('200 OK');
		header('Content-type: text/xml; charset=utf-8');

		if(
			strpos(serverSet('HTTP_ACCEPT_ENCODING'), 'gzip') !== false && 
			@extension_loaded('zlib') && 
			@ini_get('zlib.output_compression') == 0 && 
			@ini_get('output_handler') != 'ob_gzhandler' &&
			!@headers_sent()
		)
		{
			header('Content-Encoding: gzip');
			$xml = gzencode($xml);
		}

		echo $xml;
		exit;
	}

	/**
	 * Renders a &lt;url&gt; element to the XML document.
	 *
	 * @param  string     $url     The URL
	 * @param  int|string $lastmod The modification date
	 * @return rah_sitemap
	 */

	public function url($url, $lastmod = NULL)
	{
		if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0)
		{
			$url = hu.ltrim($url, '/');
		}

		if (preg_match('/[\'"<>]/', $url))
		{
			$url = htmlspecialchars($url, ENT_QUOTES);
		}

		if (isset($this->urlset[$url]))
		{
			return $this;
		}

		if ($lastmod !== NULL)
		{
			if (!is_int($lastmod))
			{
				$lastmod = doArray($lastmod, 'strtotime');
			}

			if ($lastmod !== false)
			{
				$lastmod = safe_strftime('iso8601', $lastmod);
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
	 * Picks up names of article fields.
	 *
	 * @return rah_sitemap
	 */

	protected function populate_article_fields()
	{
		$columns = (array) @getThings('describe '.safe_pfx('textpattern'));

		foreach ($columns as $name)
		{
			$this->article_fields[strtolower($name)] = $name;
		}

		foreach (getCustomFields() as $id => $name)
		{
			$this->article_fields[$name] = 'custom_'.intval($id);
		}

		return $this;
	}

	/**
	 * Options panel.
	 */

	public function prefs()
	{
		echo 
			'<p>'.n.
			'	<a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_sitemap_exclude_fields">'.gTxt('rah_sitemap_view_prefs').'</a><br />'.n.
			'	<a href="'.hu.'?rah_sitemap=sitemap">'.gTxt('rah_sitemap_view_sitemap').'</a>'.
			'</p>';
	}

	/**
	 * Shows settings at the Sections panel.
	 *
	 * @param  string $event The event
	 * @param  string $step  The step
	 * @param  bool   $void  Not used
	 * @param  array  $r     The section data as an array
	 * @return string HTML
	 */

	public function section_ui($event, $step, $void, $r)
	{
		if ($r['name'] !== 'default')
		{
			return inputLabel('rah_sitemap_include_in', yesnoradio('rah_sitemap_include_in', !empty($r['rah_sitemap_include_in']), '', ''), '', 'rah_sitemap_include_in');
		}
	}

	/**
	 * Updates a section.
	 */

	public function section_save()
	{
		safe_update(
			'txp_section', 
			'rah_sitemap_include_in='.intval(ps('rah_sitemap_include_in')),
			"name='".doSlash(ps('name'))."'"
		);
	}

	/**
	 * Shows settings at the Category panel.
	 *
	 * @param  string $event The event
	 * @param  string $step  The step
	 * @param  bool   $void  Not used
	 * @param  array  $r     The section data as an array
	 * @return string HTML
	 */

	public function category_ui($event, $step, $void, $r)
	{
		return inputLabel('rah_sitemap_include_in', yesnoradio('rah_sitemap_include_in', !empty($r['rah_sitemap_include_in']), '', ''), '', 'rah_sitemap_include_in');
	}

	/**
	 * Updates a category.
	 */

	public function category_save()
	{
		safe_update(
			'txp_category', 
			'rah_sitemap_include_in='.intval(ps('rah_sitemap_include_in')),
			'id='.intval(ps('id'))
		);
	}
}

?>