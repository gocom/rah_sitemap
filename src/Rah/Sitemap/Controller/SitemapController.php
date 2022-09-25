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
    private Rah_Sitemap_RecordInterface $record;
    private Rah_Sitemap_ResponseFactory $responseFactory;
    private Rah_Sitemap_GetUrlNodeAction $getUrlNodeAction;
    private int $page;

    /**
     * Constructor.
     *
     * @param Rah_Sitemap_RecordInterface $record
     * @param Rah_Sitemap_ResponseFactory $responseFactory
     * @param Rah_Sitemap_GetUrlNodeAction $getUrlNodeAction
     * @param int $page
     */
    public function __construct(
        Rah_Sitemap_RecordInterface $record,
        Rah_Sitemap_ResponseFactory $responseFactory,
        Rah_Sitemap_GetUrlNodeAction $getUrlNodeAction,
        int $page
    ) {
        $this->record = $record;
        $this->responseFactory = $responseFactory;
        $this->getUrlNodeAction = $getUrlNodeAction;
        $this->page = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): ?Rah_Sitemap_Response
    {
        $urls = $this->record->getUrls($this->page);

        if (!$urls) {
            return null;
        }

        $out = [];

        foreach ($urls as $url) {
            $out[] = $this->getUrlNodeAction->execute($url);
        }

        $xml =
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">'.
            implode('', $out).
            '</urlset>';

        $response = $this->responseFactory->create();

        $response
            ->setHeaders([
                'Content-type' => 'text/xml; charset=utf-8',
            ])
            ->setCompress(true)
            ->setBody($xml);

        return $response;
    }
}
