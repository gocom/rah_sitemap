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
 * Robots controller.
 */
final class Rah_Sitemap_Controller_RobotsController implements Rah_Sitemap_ControllerInterface
{
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
     * {@inheritdoc}
     */
    public function execute(): void
    {
        ob_clean();
        txp_status_header('200 OK');
        header('Content-type: text/plain; charset=utf-8');

        if ($this->isClean) {
            echo 'Sitemap: '.hu.'sitemap.xml';
        } else {
            echo 'Sitemap: '.hu.'?rah_sitemap=sitemap.xml';
        }

        exit;
    }
}
