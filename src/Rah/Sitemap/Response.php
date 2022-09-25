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
 * Response.
 */
final class Rah_Sitemap_Response
{
    /**
     * Headers.
     *
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * Body.
     *
     * @var string
     */
    private string $body = '';

    /**
     * Whether the response can be compressed.
     *
     * @var bool
     */
    private bool $compress = false;

    /**
     * Sets status.
     *
     * @var string
     */
    private string $status = '200 OK';

    /**
     * Sets header.
     *
     * @param array<string, string> $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets status.
     *
     * @param string $status
     *
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Sets body.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setBody(string $content): self
    {
        $this->body = $content;

        return $this;
    }

    /**
     * Gets body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Sets whether the response can be compressed.
     *
     * @param bool $canCompress
     *
     * @return $this
     */
    public function setCompress(bool $canCompress): self
    {
        $this->compress = $canCompress;

        return $this;
    }

    /**
     * Whether the response can be compressed.
     *
     * @return bool
     */
    public function canCompress(): bool
    {
        return $this->compress;
    }
}
