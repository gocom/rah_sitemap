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
 * Abstract record.
 */
abstract class Rah_Sitemap_Record_AbstractRecord implements Rah_Sitemap_RecordInterface
{
    private const DEFAULT_LIMIT = 50000;

    /**
     * Gets limit.
     *
     * @return int
     */
    protected function getLimit(): int
    {
        return max(1, (int) get_pref('rah_sitemap_limit') ?: self::DEFAULT_LIMIT);
    }

    /**
     * Gets offset.
     *
     * @return int
     */
    protected function getOffset(): int
    {
        $limit = $this->getLimit();

        return max(0, ($page * $limit) - $limit);
    }

    /**
     * Counts number of pages based on the given number of items.
     *
     * @param int $itemCount
     *
     * @return int
     */
    protected function countPages(int $itemCount): int
    {
        return (int) ceil($itemCount / $this->getLimit());
    }
}
