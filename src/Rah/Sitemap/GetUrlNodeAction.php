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
 * Get URL node action.
 */
final class Rah_Sitemap_GetUrlNodeAction
{
    /**
     * Gets URL node.
     *
     * @param Rah_Sitemap_Url $url
     *
     * @return string
     */
    public function execute(Rah_Sitemap_Url $url): string
    {
        $address = $url->getUrl();
        $modifiedAt = $url->getModifiedAt();

        if (strpos($address, 'http://') !== 0
            && strpos($address, 'https://') !== 0
        ) {
            $address = hu . ltrim($address, '/');
        }

        if (preg_match('/[\'"<>]/', $address)) {
            $address = htmlspecialchars($address, ENT_QUOTES);
        }

        if ($modifiedAt !== null) {
            $modifiedAt = safe_strftime('c', $modifiedAt);
        }

        return '<url>'.
            '<loc>'.$address.'</loc>'.
            ($modifiedAt ? '<lastmod>'.$modifiedAt.'</lastmod>' : '').
            '</url>';
    }
}
