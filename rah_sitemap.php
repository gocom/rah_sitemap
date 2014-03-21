<?php

/*
 * rah_sitemap - Flat templates for Textpattern CMS
 * https://github.com/gocom/rah_sitemap
 *
 * Copyright (C) 2014 Jukka Svahn
 *
 * This file is part of rah_sitemap.
 *
 * rah_sitemap is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_sitemap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_sitemap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Main plugin class.
 */

class Rah_Sitemap
{
    /**
     * Stores an XML urlset.
     *
     * @var array
     */

    protected $urlset = array();

    /**
     * Stores an array of mapped article fields.
     *
     * @var array
     */

    protected $articleFields = array();

    /**
     * Installer.
     */

    public function install()
    {
        $opt = array(
            'exclude_fields'          => array('pref_longtext_input', array()),
            'urls'                    => array('pref_longtext_input', ''),
            'future_articles'         => array('yesnoradio', 0),
            'past_articles'           => array('yesnoradio', 1),
            'expired_articles'        => array('yesnoradio', 1),
            'exclude_sticky_articles' => array('yesnoradio', 1),
            'compress'                => array('yesnoradio', 0),
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
                'sections'   => array(),
                'categories' => array(),
            );

            $rs = safe_rows('name, value', 'rah_sitemap_prefs', '1=1');

            foreach ($rs as $a)
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

                else if ($a['name'] == 'sections')
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
            $n = 'rah_sitemap_'.$name;

            if (get_pref($n, false) === false)
            {
                if (is_array($val[1]))
                {
                    $val[1] = implode(',', $val[1]);
                }

                set_pref($n, $val[1], 'rah_sitemap', PREF_ADVANCED, $val[0], $position);
            }

            $position++;
        }
    }

    /**
     * Uninstaller.
     */

    public function uninstall()
    {
        safe_delete('txp_prefs', "name like 'rah\_sitemap\_%'");
        safe_alter('txp_section', 'DROP COLUMN rah_sitemap_include_in');
        safe_alter('txp_category', 'DROP COLUMN rah_sitemap_include_in');
    }

    /**
     * Constructor.
     */

    public function __construct()
    {
        add_privs('plugin_prefs.rah_sitemap', '1,2');
        add_privs('prefs.rah_sitemap', '1,2');
        register_callback(array($this, 'install'), 'plugin_lifecycle.rah_sitemap', 'installed');
        register_callback(array($this, 'uninstall'), 'plugin_lifecycle.rah_sitemap', 'deleted');
        register_callback(array($this, 'prefs'), 'plugin_prefs.rah_sitemap');
        register_callback(array($this, 'page_handler'), 'textpattern');
        register_callback(array($this, 'renderSectionOptions'), 'section_ui', 'extend_detail_form');
        register_callback(array($this, 'renderCategoryOptions'), 'category_ui', 'extend_detail_form');
        register_callback(array($this, 'saveSection'), 'section', 'section_save');
        register_callback(array($this, 'saveCategory'), 'category', 'cat_article_save');
        register_callback(array($this, 'saveCategory'), 'category', 'cat_image_save');
        register_callback(array($this, 'saveCategory'), 'category', 'cat_file_save');
        register_callback(array($this, 'saveCategory'), 'category', 'cat_link_save');
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

        return $this->populateArticleFields()->get_sitemap();
    }

    /**
     * Generates and outputs the sitemap.
     */

    protected function get_sitemap()
    {
        $this->addUrl(hu);

        $rs = safe_rows_start(
            'name',
            'txp_section',
            "name != 'default' and rah_sitemap_include_in = 1 order by name asc"
        );

        if ($rs)
        {
            while ($a = nextRow($rs))
            {
                $this->addUrl(pagelinkurl(array('s' => $a['name'])));
            }
        }

        $rs = safe_rows_start(
            'name, type',
            'txp_category',
            "name != 'root' and rah_sitemap_include_in = 1 order by name asc"
        );

        if ($rs)
        {
            while ($a = nextRow($rs))
            {
                $this->addUrl(pagelinkurl(array('c' => $a['name'], 'context' => $a['type'])));
            }
        }

        $sql = array('Status >= 4');

        foreach (do_list(get_pref('rah_sitemap_exclude_fields')) as $field)
        {
            if ($field)
            {
                $f = explode(':', $field);
                $n = strtolower(trim($f[0]));

                if (isset($this->articleFields[$n]))
                {
                    $sql[] = $this->articleFields[$n]." NOT LIKE '".doSlash(trim(implode(':', array_slice($f, 1))))."'";
                }
            }
        }

        if (get_pref('rah_sitemap_exclude_sticky_articles'))
        {
            $sql[] = 'Status != 5';
        }

        if (!get_pref('rah_sitemap_future_articles'))
        {
            $sql[] = 'Posted <= now()';
        }

        if (!get_pref('rah_sitemap_past_articles'))
        {
            $sql[] = 'Posted >= now()';
        }

        if (!get_pref('rah_sitemap_expired_articles'))
        {
            $sql[] = "(Expires = ".NULLDATETIME." or Expires >= now())";
        }

        $rs = safe_rows_start(
            '*, unix_timestamp(Posted) as uPosted, unix_timestamp(LastMod) as uLastMod',
            'textpattern',
            implode(' and ', $sql) . ' order by Posted desc'
        );

        if ($rs)
        {
            while ($a = nextRow($rs))
            {
                $this->addUrl(permlinkurl($a), (int) max($a['uLastMod'], $a['uPosted']));
            }
        }

        foreach (do_list(get_pref('rah_sitemap_urls')) as $url)
        {
            if ($url)
            {
                $this->addUrl($url);
            }
        }

        $urlset = array();
        callback_event_ref('rah_sitemap.urlset', '', 0, $urlset);

        if ($urlset && is_array($urlset))
        {
            foreach ($urlset as $url => $lastmod)
            {
                $this->addUrl($url, $lastmod);
            }
        }

        $xml = 
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
            implode('', array_slice($this->addUrlset, 0, 50000)).
            '</urlset>';

        ob_clean();
        txp_status_header('200 OK');
        header('Content-type: text/xml; charset=utf-8');

        if (
            get_pref('rah_sitemap_compress') && 
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

    protected function addUrl($url, $lastmod = null)
    {
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0)
        {
            $url = hu.ltrim($url, '/');
        }

        if (preg_match('/[\'"<>]/', $url))
        {
            $url = htmlspecialchars($url, ENT_QUOTES);
        }

        if (isset($this->addUrlset[$url]))
        {
            return $this;
        }

        if ($lastmod !== null)
        {
            if (!is_int($lastmod))
            {
                $lastmod = strtotime($lastmod);
            }

            if ($lastmod !== false)
            {
                $lastmod = safe_strftime('iso8601', $lastmod);
            }
        }

        $this->addUrlset[$url] = 
            '<url>'.
                '<loc>'.$url.'</loc>'.
                ($lastmod ? '<lastmod>'.$lastmod.'</lastmod>' : '').
            '</url>';

        return $this;
    }

    /**
     * Picks up names of article fields.
     *
     * @return Rah_Sitemap
     */

    protected function populateArticleFields()
    {
        $columns = (array) @getThings('describe '.safe_pfx('textpattern'));

        foreach ($columns as $name)
        {
            $this->articleFields[strtolower($name)] = $name;
        }

        foreach (getCustomFields() as $id => $name)
        {
            $this->articleFields[$name] = 'custom_'.intval($id);
        }

        return $this;
    }

    /**
     * Options panel.
     */

    public function prefs()
    {
        pagetop(gTxt('rah_sitemap'));

        echo 
            '<p>'.n.
            '    <a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_sitemap_exclude_fields">'.gTxt('rah_sitemap_view_prefs').'</a><br />'.n.
            '    <a href="'.hu.'?rah_sitemap=sitemap">'.gTxt('rah_sitemap_view_sitemap').'</a>'.
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

    public function renderSectionOptions($event, $step, $void, $r)
    {
        if ($r['name'] !== 'default')
        {
            return inputLabel('rah_sitemap_include_in', yesnoradio('rah_sitemap_include_in', !empty($r['rah_sitemap_include_in']), '', ''), '', 'rah_sitemap_include_in');
        }
    }

    /**
     * Updates a section.
     */

    public function saveSection()
    {
        safe_update(
            'txp_section',
            'rah_sitemap_include_in = '.intval(ps('rah_sitemap_include_in')),
            "name = '".doSlash(ps('name'))."'"
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

    public function renderCategoryOptions($event, $step, $void, $r)
    {
        return inputLabel('rah_sitemap_include_in', yesnoradio('rah_sitemap_include_in', !empty($r['rah_sitemap_include_in']), '', ''), '', 'rah_sitemap_include_in');
    }

    /**
     * Updates a category.
     */

    public function saveCategory()
    {
        safe_update(
            'txp_category',
            'rah_sitemap_include_in = '.intval(ps('rah_sitemap_include_in')),
            'id = '.intval(ps('id'))
        );
    }
}

new Rah_Sitemap();
