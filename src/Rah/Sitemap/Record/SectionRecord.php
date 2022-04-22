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
 * Sections.
 */
class Rah_Sitemap_Record_SectionRecord implements Rah_Sitemap_RecordInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function getPages(): int
    {
        return (int) safe_count(
            'txp_section',
            "name != 'default' and rah_sitemap_include_in = 1"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls(int $page): array
    {
        $urls = [];

        $rs = safe_rows_start(
            'name',
            'txp_section',
            "name != 'default' and rah_sitemap_include_in = 1 order by name asc"
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                $urls[] = new Rah_Sitemap_Url(
                    pagelinkurl([
                        's' => $a['name'],
                    ])
                );
            }
        }

        return $urls;
    }
}
