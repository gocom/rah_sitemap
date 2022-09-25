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
 * Send response action.
 */
final class Rah_Sitemap_SendResponseAction
{
    /**
     * Sends the given response.
     *
     * @param Rah_Sitemap_Response $response
     */
    public function execute(Rah_Sitemap_Response $response): void
    {
        ob_clean();

        txp_status_header($response->getStatus());

        $headers = $response->getHeaders();
        $body = $response->getBody();
        $canCompress = $response->canCompress() && $this->isCompressionSupported();

        if ($canCompress) {
            $headers['Content-Encoding'] = 'gzip';
            $body = gzencode($body);
        }

        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $body;
        exit;
    }

    /**
     * Whether compression is supported.
     *
     * @return bool
     */
    private function isCompressionSupported(): bool
    {
        return get_pref('rah_sitemap_compress')
            && strpos(serverSet('HTTP_ACCEPT_ENCODING'), 'gzip') !== false;
    }
}
