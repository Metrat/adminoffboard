<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AdminOffboard\Response;

use OCP\AppFramework\Http\JSONResponse;

/**
 * Standard success response
 */
class SuccessResponse extends JSONResponse
{
    /**
     * Create a success response
     */
    public function __construct(
        $data = null,
        string $message = 'Success',
        int $status = 200,
        ?array $meta = null
    ) {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        parent::__construct($response, $status);
    }

    /**
     * Create a paginated success response
     */
    public static function paginated(
        $data,
        int $total,
        int $limit,
        int $offset,
        string $message = 'Success'
    ): self {
        $meta = [
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'next_offset' => ($offset + $limit < $total) ? $offset + $limit : null,
                'total_pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
                'current_page' => $limit > 0 ? (int)floor($offset / $limit) + 1 : 1
            ]
        ];

        return new self($data, $message, 200, $meta);
    }

    /**
     * Create a created success response (HTTP 201)
     */
    public static function created($data = null, string $message = 'Created'): self
    {
        return new self($data, $message, 201);
    }

    /**
     * Create an accepted success response (HTTP 202)
     */
    public static function accepted($data = null, string $message = 'Accepted'): self
    {
        return new self($data, $message, 202);
    }

    /**
     * Create a no content success response (HTTP 204)
     */
    public static function noContent(string $message = 'No content'): self
    {
        return new self(null, $message, 204);
    }

    /**
     * Create a success response with custom meta data
     */
    public static function withMeta($data, array $meta, string $message = 'Success'): self
    {
        return new self($data, $message, 200, $meta);
    }

    /**
     * Create a success response with additional data
     */
    public static function withExtra($data, array $extra, string $message = 'Success'): self
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        foreach ($extra as $key => $value) {
            $response[$key] = $value;
        }

        return new self($data, $message, 200, null);
    }
}