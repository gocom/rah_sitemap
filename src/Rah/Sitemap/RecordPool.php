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
 * Record pool.
 */
final class Rah_Sitemap_RecordPool
{
    /**
     * Gets sitemaps.
     *
     * @return Rah_Sitemap_RecordInterface[]
     */
    public function getSitemaps(): array
    {
        $sitemaps = [
            new Rah_Sitemap_Record_ArticleRecord(),
            new Rah_Sitemap_Record_CategoryRecord(),
            new Rah_Sitemap_Record_SectionRecord(),
            new Rah_Sitemap_Record_SiteRecord(),
        ];

        callback_event_ref('rah_sitemap.sitemaps', '', 0, $sitemaps);

        return $sitemaps;
    }
}
