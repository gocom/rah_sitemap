<?php

/*
 * rah_sitemap - XML sitemap plugin for Textpattern CMS
 * https://github.com/gocom/rah_sitemap
 *
 * Copyright (C) 2022 Jukka Svahn
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
     * Constructor.
     */
    public function __construct()
    {
        add_privs('plugin_prefs.rah_sitemap', '1,2');
        add_privs('prefs.rah_sitemap', '1,2');
        register_callback([$this, 'install'], 'plugin_lifecycle.rah_sitemap', 'installed');
        register_callback([$this, 'uninstall'], 'plugin_lifecycle.rah_sitemap', 'deleted');
        register_callback([$this, 'prefs'], 'plugin_prefs.rah_sitemap');
        register_callback([$this, 'handleRawUrl'], 'textpattern');
        register_callback([$this, 'handleCleanUrl'], 'txp_die', '404');
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
            'include_article_categories' => ['yesnoradio', 1],
            'include_image_categories' => ['yesnoradio', 1],
            'include_file_categories' => ['yesnoradio', 1],
            'include_link_categories' => ['yesnoradio', 1],
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
     * Handles raw URLs.
     */
    public function handleRawUrl(): void
    {
        $path = gps('rah_sitemap');

        if ($path) {
            $router = new Rah_Sitemap_Router(false);

            $router->route((string) $path);
        }
    }

    /**
     * Handles routing clean URLs.
     */
    public function handleCleanUrl(): void
    {
        global $pretext;

        $path = explode('?', (string) ($pretext['request_uri'] ?? ''));
        $path = basename(array_shift($path));

        if ($path) {
            $router = new Rah_Sitemap_Router(true);

            $router->route($path);
        }
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
     * @param string $event The event
     * @param string $step The step
     * @param bool $void Not used
     * @param array $r The section data as an array
     *
     * @return string HTML
     */
    public function renderSectionOptions($event, $step, $void, $r): string
    {
        if ($r['name'] !== 'default') {
            return inputLabel(
                'rah_sitemap_include_in',
                yesnoradio('rah_sitemap_include_in', $r['rah_sitemap_include_in'] ?? '0', '', ''),
                '',
                'rah_sitemap_include_in'
            );
        }

        return '';
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
     * @param string $event The event
     * @param string $step The step
     * @param bool $void Not used
     * @param array $r The section data as an array
     *
     * @return string HTML
     */
    public function renderCategoryOptions($event, $step, $void, $r): string
    {
        return inputLabel(
            'rah_sitemap_include_in',
            yesnoradio('rah_sitemap_include_in', $r['rah_sitemap_include_in'] ?? '0', '', ''),
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
