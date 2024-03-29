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
 * Record.
 */
interface Rah_Sitemap_RecordInterface
{
    /**
     * Gets name of the sitemap.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Gets number of pages.
     *
     * @return int
     */
    public function getPages(): int;

    /**
     * Gets URLs for the given page offset.
     *
     * @param int $page
     *
     * @return Rah_Sitemap_Url[]
     */
    public function getUrls(int $page): array;
}
