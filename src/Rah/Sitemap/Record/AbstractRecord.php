<?php

/*
 * rah_sitemap - XML sitemap plugin for Textpattern CMS
 * https://github.com/gocom/rah_sitemap
 *
 * Copyright (C) 2026 Jukka Svahn
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
 * Abstract record.
 */
abstract class Rah_Sitemap_Record_AbstractRecord implements Rah_Sitemap_RecordInterface
{
    private const DEFAULT_LIMIT = 50000;
    private const DEFAULT_CHUNK_SIZE = 5000;

    /**
     * Gets limit.
     *
     * @return int
     */
    protected function getLimit(): int
    {
        return max(1, (int) get_pref('rah_sitemap_limit') ?: self::DEFAULT_LIMIT);
    }

    /**
     * Gets chunk size.
     *
     * @return int
     */
    protected function getChunkSize(): int
    {
        $chunkSize = max(1, (int) get_pref('rah_sitemap_chunk_size') ?: self::DEFAULT_CHUNK_SIZE);

        return min($this->getLimit(), $chunkSize);
    }

    /**
     * Gets offset.
     *
     * @param int $page
     *
     * @return int
     */
    protected function getOffset(int $page): int
    {
        $limit = $this->getLimit();

        return max(0, ($page * $limit) - $limit);
    }

    /**
     * Counts number of pages based on the given number of items.
     *
     * @param int $itemCount
     *
     * @return int
     */
    protected function countPages(int $itemCount): int
    {
        return (int) ceil($itemCount / $this->getLimit());
    }

    /**
     * Gets chunked limit.
     *
     * Allows buffering rows from the database in smaller chunks to
     * limit peak memory usage.
     *
     * Will return NULL, if we have reached the last chunk and there is
     * nothing more to return.
     *
     * @param int $chunk
     *
     * @return int|null
     */
    protected function getChunkedLimit(int $chunk): ?int
    {
        $limit = $this->getLimit();
        $chunkSize = $this->getChunkSize();
        $chunkOffset = max(0, ($chunkSize * $chunk) - $chunkSize);

        if ($chunkOffset >= $limit) {
            return null;
        }

        $left = $limit - $chunkOffset;

        if ($left < $chunkSize) {
            return $left;
        }

        return $chunkSize;
    }

    /**
     * Gets chunked offset.
     *
     * Allows buffering rows from the database in smaller chunks to
     * limit peak memory usage.
     *
     * @param int $page
     * @param int $chunk
     *
     * @return int
     */
    protected function getChunkedOffset(int $page, int $chunk): int
    {
        $chunkSize = $this->getChunkSize();

        return max(0, $this->getOffset($page) + ($chunk * $chunkSize) - $chunkSize);
    }
}
