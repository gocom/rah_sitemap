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
class Rah_Sitemap_Record_SectionRecord extends Rah_Sitemap_Record_AbstractRecord implements Rah_Sitemap_RecordInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'section';
    }

    /**
     * {@inheritdoc}
     */
    public function getPages(): int
    {
        $items = (int) safe_count(
            'txp_section',
            "name != 'default' and rah_sitemap_include_in = 1"
        );

        return $this->countPages($items);
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
            sprintf(
                '%s order by name asc limit %s, %s',
                $this->getWhereStatement(),
                $this->getOffset($page),
                $this->getLimit()
            )
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

    /**
     * Gets SQL where statement.
     *
     * @return string
     */
    private function getWhereStatement(): string
    {
        return "name != 'default' and rah_sitemap_include_in = 1";
    }
}
