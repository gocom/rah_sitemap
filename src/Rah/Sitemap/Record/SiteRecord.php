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
 * Site.
 */
class Rah_Sitemap_Record_SiteRecord implements Rah_Sitemap_RecordInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'site';
    }

    /**
     * {@inheritdoc}
     */
    public function getPages(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls(int $page): array
    {
        $urls = [
            new Rah_Sitemap_Url(
                hu
            ),
        ];

        callback_event_ref('rah_sitemap.urlset', '', 0, $urlset);

        foreach ($urlset as $url) {
            if ($url) {
                $urls[] = new Rah_Sitemap_Url($url);
            }
        }

        foreach (do_list(get_pref('rah_sitemap_urls')) as $url) {
            if ($url) {
                $urls[] = new Rah_Sitemap_Url($url);
            }
        }

        return $urls;
    }
}
