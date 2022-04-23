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
 * Router.
 */
final class Rah_Sitemap_Router
{
    /**
     * Whether clean URLs are used.
     *
     * @var bool
     */
    private bool $isClean;

    /**
     * Constructor.
     *
     * @param bool $isClean
     */
    public function __construct(
        bool $isClean
    ) {
        $this->isClean = $isClean;
    }

    /**
     * Routes the given path to a controller.
     *
     * @param string $path
     */
    public function route(string $path): void
    {
        if ($path === 'robots.txt') {
            $controller = new Rah_Sitemap_Controller_RobotsController(
                $this->isClean
            );

            $controller->execute();

            return;
        }

        if (!$this->isClean) {
            $path .= '.xml';
        }

        $recordPool = new Rah_Sitemap_RecordPool();

        if (!$path
            || !preg_match(
                '/^sitemap(?:\.(?P<name>[a-z0-9_-]+))?(?:\.(?P<page>[0-9]+))?\.xml$/',
                $path,
                $m
            )
        ) {
            return;
        }

        $name = $m['name'] ?? null;

        if (!$name) {
            $controller = new Rah_Sitemap_Controller_IndexController(
                $recordPool,
                $this->isClean
            );

            $controller->execute();
        }

        foreach ($recordPool->getSitemaps() as $sitemap) {
            if ($sitemap->getName() === $name) {
                $page = (int) ($m['page'] ?? 1);

                $controller = new Rah_Sitemap_Controller_SitemapController(
                    $sitemap,
                    $page
                );

                $controller->execute();
            }
        }

        txp_die(gTxt('404_not_found'), '404');
    }
}
