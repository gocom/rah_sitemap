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
 * Sitemap controller.
 */
final class Rah_Sitemap_Controller_SitemapController implements Rah_Sitemap_ControllerInterface
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:sP';
    private Rah_Sitemap_RecordInterface $record;
    private int $page;

    /**
     * Constructor.
     *
     * @param Rah_Sitemap_RecordInterface $record
     * @param int $page
     */
    public function __construct(
        Rah_Sitemap_RecordInterface $record,
        int $page
    ) {
        $this->record = $record;
        $this->page = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): void
    {
        $urls = $this->record->getUrls($this->page);

        if (!$urls) {
            return;
        }

        $out = [];

        foreach ($urls as $url) {
            $out[] = $this->getUrlNode($url);
        }

        $xml =
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.
            implode('', $out).
            '</urlset>';

        ob_clean();
        txp_status_header('200 OK');
        header('Content-type: text/xml; charset=utf-8');

        if (get_pref('rah_sitemap_compress') &&
            strpos(serverSet('HTTP_ACCEPT_ENCODING'), 'gzip') !== false
        ) {
            header('Content-Encoding: gzip');
            $xml = gzencode($xml);
        }

        echo $xml;
        exit;
    }

    /**
     * Gets URL node.
     *
     * @param Rah_Sitemap_Url $url
     *
     * @return string
     */
    private function getUrlNode(Rah_Sitemap_Url $url): string
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
            $modifiedAt = date(self::DATE_FORMAT, $modifiedAt);
        }

        return '<url>'.
            '<loc>'.$address.'</loc>'.
            ($modifiedAt ? '<lastmod>'.$modifiedAt.'</lastmod>' : '').
            '</url>';
    }
}
