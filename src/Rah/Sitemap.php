<?php

/*
 * rah_sitemap - XML sitemap plugin for Textpattern CMS
 * https://github.com/gocom/rah_sitemap
 *
 * Copyright (C) 2019 Jukka Svahn
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
 * Plugin class.
 */
final class Rah_Sitemap
{
    /**
     * @var int URL limit.
     */
    private const URL_LIMIT = 50000;

    /**
     * Stores an XML urlset.
     *
     * @var string[]
     */
    private $urlset = [];

    /**
     * Stores an array of mapped article fields.
     *
     * @var array
     */
    private $articleFields = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_privs('plugin_prefs.rah_sitemap', '1,2');
        add_privs('prefs.rah_sitemap', '1,2');
        register_callback([$this, 'install'], 'plugin_lifecycle.rah_sitemap', 'installed');
        register_callback([$this, 'uninstall'], 'plugin_lifecycle.rah_sitemap', 'deleted');
        register_callback([$this, 'prefs'], 'plugin_prefs.rah_sitemap');
        register_callback([$this, 'pageHandler'], 'textpattern');
        register_callback([$this, 'cleanUrlHandler'], 'txp_die', '404');
        register_callback([$this, 'renderSectionOptions'], 'section_ui', 'extend_detail_form');
        register_callback([$this, 'renderCategoryOptions'], 'category_ui', 'extend_detail_form');
        register_callback([$this, 'saveSection'], 'section', 'section_save');
        register_callback([$this, 'saveCategory'], 'category', 'cat_article_save');
        register_callback([$this, 'saveCategory'], 'category', 'cat_image_save');
        register_callback([$this, 'saveCategory'], 'category', 'cat_file_save');
        register_callback([$this, 'saveCategory'], 'category', 'cat_link_save');
    }

    /**
     * Installer.
     */
    public function install(): void
    {
        $options = [
            'exclude_fields' => ['pref_longtext_input', ''],
            'urls' => ['pref_longtext_input', ''],
            'future_articles' => ['yesnoradio', 0],
            'past_articles' => ['yesnoradio', 1],
            'expired_articles' => ['yesnoradio', 1],
            'exclude_sticky_articles' => ['yesnoradio', 1],
            'compress' => ['yesnoradio', 0],
        ];

        if (!in_array('rah_sitemap_include_in', getThings('describe '.safe_pfx('txp_section')))) {
            safe_alter('txp_section', 'ADD rah_sitemap_include_in TINYINT(1) NOT NULL DEFAULT 1');
        }

        if (!in_array('rah_sitemap_include_in', getThings('describe '.safe_pfx('txp_category')))) {
            safe_alter('txp_category', 'ADD rah_sitemap_include_in TINYINT(1) NOT NULL DEFAULT 1');
        }

        $position = 260;

        foreach ($options as $name => $value) {
            create_pref('rah_sitemap_' . $name, $value[1], 'rah_sitemap', PREF_PLUGIN, $value[0], $position++);
        }
    }

    /**
     * Uninstaller.
     */
    public function uninstall(): void
    {
        remove_pref(null, 'rah_sitemap');
        safe_alter('txp_section', 'DROP COLUMN rah_sitemap_include_in');
        safe_alter('txp_category', 'DROP COLUMN rah_sitemap_include_in');
    }

    /**
     * Handles routing GET requests to the sitemap.
     */
    public function pageHandler(): void
    {
        if (gps('rah_sitemap')) {
            $this->populateArticleFields()->sendSitemap();
        }
    }

    /**
     * Handles routing clean URLs.
     */
    public function cleanUrlHandler(): void
    {
        global $pretext;

        $basename = explode('?', (string) $pretext['request_uri']);
        $basename = basename(array_shift($basename));

        if ($basename === 'robots.txt') {
            $this->sendRobots();
        }

        if ($basename === 'sitemap.xml' || $basename === 'sitemap.xml.gz') {
            $this->populateArticleFields()->sendSitemap();
        }
    }

    /**
     * Generates and outputs robots file.
     */
    private function sendRobots(): void
    {
        ob_clean();
        txp_status_header('200 OK');
        header('Content-type: text/plain; charset=utf-8');
        echo 'Sitemap: '.hu.'sitemap.xml';
        exit;
    }

    /**
     * Generates and outputs the sitemap.
     */
    private function sendSitemap(): void
    {
        $this->addUrl(hu);

        $rs = safe_rows_start(
            'name',
            'txp_section',
            "name != 'default' and rah_sitemap_include_in = 1 order by name asc"
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                $this->addUrl(pagelinkurl(['s' => $a['name']]));
            }
        }

        $rs = safe_rows_start(
            'name, type',
            'txp_category',
            "name != 'root' and rah_sitemap_include_in = 1 order by name asc"
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                $this->addUrl(pagelinkurl([
                    'c' => $a['name'],
                    'context' => $a['type'],
                ]));
            }
        }

        $sql = ['Status >= 4'];

        foreach (do_list(get_pref('rah_sitemap_exclude_fields')) as $field) {
            if ($field) {
                $f = explode(':', $field);
                $n = strtolower(trim($f[0]));

                if (isset($this->articleFields[$n])) {
                    $sql[] = $this->articleFields[$n]." NOT LIKE '".doSlash(trim(implode(':', array_slice($f, 1))))."'";
                }
            }
        }

        if (get_pref('rah_sitemap_exclude_sticky_articles')) {
            $sql[] = 'Status != 5';
        }

        if (!get_pref('rah_sitemap_future_articles')) {
            $sql[] = 'Posted <= now()';
        }

        if (!get_pref('rah_sitemap_past_articles')) {
            $sql[] = 'Posted >= now()';
        }

        if (!get_pref('rah_sitemap_expired_articles')) {
            $sql[] = "(Expires = NULL or Expires >= now())";
        }

        $rs = safe_rows_start(
            '*, unix_timestamp(Posted) as posted, unix_timestamp(LastMod) as uLastMod',
            'textpattern',
            implode(' and ', $sql) . ' order by Posted desc'
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                $this->addUrl(permlinkurl($a), (int) max($a['uLastMod'], $a['posted']));
            }
        }

        foreach (do_list(get_pref('rah_sitemap_urls')) as $url) {
            if ($url) {
                $this->addUrl($url);
            }
        }

        $urlset = [];

        callback_event_ref('rah_sitemap.urlset', '', 0, $urlset);

        if ($urlset && is_array($urlset)) {
            foreach ($urlset as $url => $lastmod) {
                $this->addUrl($url, $lastmod);
            }
        }

        $xml =
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
            implode('', array_slice($this->urlset, 0, self::URL_LIMIT)).
            '</urlset>';

        ob_clean();
        txp_status_header('200 OK');
        header('Content-type: text/xml; charset=utf-8');

        if (get_pref('rah_sitemap_compress') &&
            strpos(serverSet('HTTP_ACCEPT_ENCODING'), 'gzip') !== false
        ) {
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
     *
     * @return $this
     */
    private function addUrl($url, $lastmod = null): self
    {
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = hu.ltrim($url, '/');
        }

        if (preg_match('/[\'"<>]/', $url)) {
            $url = htmlspecialchars($url, ENT_QUOTES);
        }

        if (isset($this->urlset[$url])) {
            return $this;
        }

        if ($lastmod !== null) {
            if (!is_int($lastmod)) {
                $lastmod = strtotime($lastmod);
            }

            if ($lastmod !== false) {
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
     * @return $this
     */
    private function populateArticleFields(): self
    {
        $columns = (array) @getThings('describe '.safe_pfx('textpattern'));

        foreach ($columns as $name) {
            $this->articleFields[strtolower($name)] = $name;
        }

        foreach (getCustomFields() as $id => $name) {
            $this->articleFields[$name] = 'custom_'.intval($id);
        }

        return $this;
    }

    /**
     * Options panel.
     */
    public function prefs(): void
    {
        pagetop(gTxt('rah_sitemap'));

        echo graf(
            href(gTxt('rah_sitemap_view_prefs'), ['event' => 'prefs']) . br .
            href(gTxt('rah_sitemap_view_sitemap'), hu . '?rah_sitemap=sitemap')
        );
    }

    /**
     * Shows settings at the Sections panel.
     *
     * @param  string $event The event
     * @param  string $step  The step
     * @param  bool   $void  Not used
     * @param  array  $r     The section data as an array
     *
     * @return string HTML
     */
    public function renderSectionOptions($event, $step, $void, $r): string
    {
        if ($r['name'] !== 'default') {
            $value = empty($r['rah_sitemap_include_in'])? 0 : $r['rah_sitemap_include_in'];
            return inputLabel(
                'rah_sitemap_include_in',
                yesnoradio('rah_sitemap_include_in', $value, '', ''),
                '',
                'rah_sitemap_include_in'
            );
        }
    }

    /**
     * Updates a section.
     */
    public function saveSection(): void
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
     *
     * @return string HTML
     */
    public function renderCategoryOptions($event, $step, $void, $r): string
    {
        $value = empty($r['rah_sitemap_include_in'])? 0 : $r['rah_sitemap_include_in'];
        return inputLabel(
            'rah_sitemap_include_in',
            yesnoradio('rah_sitemap_include_in', $value, '', ''),
            '',
            'rah_sitemap_include_in'
        );
    }

    /**
     * Updates a category.
     */
    public function saveCategory(): void
    {
        safe_update(
            'txp_category',
            'rah_sitemap_include_in = '.intval(ps('rah_sitemap_include_in')),
            'id = '.intval(ps('id'))
        );
    }
}
