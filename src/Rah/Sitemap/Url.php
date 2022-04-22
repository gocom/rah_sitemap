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
 * Url.
 */
final class Rah_Sitemap_Url
{
    private string $url;
    private ?int $modifiedAt;

    /**
     * Constructor.
     *
     * @param string $url
     * @param int|null $modifiedAt
     */
    public function __construct(
        string $url,
        ?int $modifiedAt = null
    ) {
        $this->url = $url;
        $this->modifiedAt = $modifiedAt;
    }

    /**
     * Gets URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Gets modified at timestamp.
     *
     * @return int|null
     */
    public function getModifiedAt(): ?int
    {
        return $this->modifiedAt;
    }
}
