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
 * Sitemap index controller.
 */
final class Rah_Sitemap_Controller_IndexController implements Rah_Sitemap_ControllerInterface
{
    private Rah_Sitemap_RecordPool $recordPool;
    private Rah_Sitemap_ResponseFactory $responseFactory;
    private bool $isClean;

    /**
     * Constructor.
     *
     * @param Rah_Sitemap_RecordPool $recordPool
     * @param Rah_Sitemap_ResponseFactory $responseFactory
     * @param bool $isClean
     */
    public function __construct(
        Rah_Sitemap_RecordPool $recordPool,
        Rah_Sitemap_ResponseFactory $responseFactory,
        bool $isClean
    ) {
        $this->recordPool = $recordPool;
        $this->responseFactory = $responseFactory;
        $this->isClean = $isClean;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): ?Rah_Sitemap_Response
    {
        $out = [];

        foreach ($this->recordPool->getSitemaps() as $sitemap) {
            $this->addSitemapNode($sitemap, $out);
        }

        if (!$out) {
            return null;
        }

        $xml =
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<sitemapindex xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">'.
            implode('', $out).
            '</sitemapindex>';

        $response = $this->responseFactory->create();

        $response
            ->setHeaders([
                'Content-type' => 'text/xml; charset=utf-8',
            ])
            ->setCompress(true)
            ->setBody($xml);

        return $response;
    }

    /**
     * Gets sitemap node.
     *
     * @param Rah_Sitemap_RecordInterface $sitemap
     * @param string[] $out
     */
    private function addSitemapNode(
        Rah_Sitemap_RecordInterface $sitemap,
        array &$out
    ): void {
        $name = $sitemap->getName();
        $pages = $sitemap->getPages();

        for ($page = 1; $pages >= $page; $page++) {
            if ($this->isClean) {
                $url = sprintf('%s%s.%s.%s.xml', hu, 'sitemap', $name, $page);
            } else {
                $url = sprintf('%s?rah_sitemap=%s.%s.%s', hu, 'sitemap', $name, $page);
            }

            $out[] = sprintf('<sitemap><loc>%s</loc></sitemap>', $url);
        }
    }
}
