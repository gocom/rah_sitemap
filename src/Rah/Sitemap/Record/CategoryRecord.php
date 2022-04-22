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
 * Categories.
 */
class Rah_Sitemap_Record_CategoryRecord implements Rah_Sitemap_RecordInterface
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
        $items = (int) safe_count(
            'txp_category',
            $this->getWhereStatement()
        );

        return \ceil($items / self::LIMIT);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls(int $page): array
    {
        $urls = [];
        $offset = max(0, ($page * self::LIMIT) - self::LIMIT);

        $rs = safe_rows_start(
            'name, type',
            'txp_category',
            sprintf(
                '%s order by name asc limit %s, %s',
                $this->getWhereStatement(),
                $offset,
                self::LIMIT
            )
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                $urls[] = new Rah_Sitemap_Url(
                    pagelinkurl([
                        'c' => $a['name'],
                        'context' => $a['type'],
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
        $sql = ["name != 'root' and rah_sitemap_include_in = 1"];

        if (!get_pref('rah_sitemap_include_article_categories')) {
            $sql[] = "type != 'article'";
        }

        if (!get_pref('rah_sitemap_include_image_categories')) {
            $sql[] = "type != 'image'";
        }

        if (!get_pref('rah_sitemap_include_file_categories')) {
            $sql[] = "type != 'file'";
        }

        if (!get_pref('rah_sitemap_include_link_categories')) {
            $sql[] = "type != 'link'";
        }

        return implode(' and ', $sql);
    }
}
