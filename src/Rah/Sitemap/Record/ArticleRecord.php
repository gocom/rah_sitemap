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
 * Articles.
 */
class Rah_Sitemap_Record_ArticleRecord extends Rah_Sitemap_Record_AbstractRecord implements Rah_Sitemap_RecordInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'article';
    }

    /**
     * {@inheritdoc}
     */
    public function getPages(): int
    {
        $items = (int) safe_count(
            'textpattern',
            $this->getWhereStatement()
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
            '*, unix_timestamp(Posted) as posted, unix_timestamp(LastMod) as uLastMod',
            'textpattern',
            sprintf(
                '%s order by Posted asc limit %s, %s',
                $this->getWhereStatement(),
                $this->getOffset($page),
                $this->getLimit()
            )
        );

        if ($rs) {
            while ($a = nextRow($rs)) {
                $urls[] = new Rah_Sitemap_Url(
                    permlinkurl($a),
                    (int) max($a['uLastMod'], $a['posted'])
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
        $articleFields = $this->getArticleFields();
        $sql = ['Status >= 4'];

        foreach (do_list(get_pref('rah_sitemap_exclude_fields')) as $pair) {
            if ($pair) {
                $parts = explode(':', $pair, 2);

                if (count($parts) === 2) {
                    $name = strtolower(trim($parts[0]));
                    $column = $articleFields[$name] ?? null;

                    if ($column) {
                        $value = doSlash(trim($parts[1]));

                        $sql[] = sprintf(
                            "%s NOT LIKE '%s'",
                            $column,
                            $value
                        );
                    }
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
            $sql[] = "(Expires IS NULL or Expires >= now())";
        }

        return implode(' and ', $sql);
    }

    /**
     * Gets an array of article field names that can be used for filtering.
     *
     * Key is lowercase column name, value is database column name in its
     * original casing.
     *
     * @return array<string, string>
     */
    private function getArticleFields(): array
    {
        $columns = (array) @getThings('describe '.safe_pfx('textpattern'));
        $fields = [];

        foreach ($columns as $name) {
            $fields[strtolower($name)] = $name;
        }

        foreach (getCustomFields() as $id => $name) {
            $fields[$name] = 'custom_'.intval($id);
        }

        return $fields;
    }
}
