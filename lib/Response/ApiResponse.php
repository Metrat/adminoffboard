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
 * Standard API response
 */
class ApiResponse
{
    /**
     * Create a success response
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): JSONResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        return new JSONResponse($response, $status);
    }

    /**
     * Create an error response
     */
    public static function error(string $message, int $status = 400, $data = null): JSONResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => $data
        ];

        return new JSONResponse($response, $status);
    }

    /**
     * Create a validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JSONResponse
    {
        return self::error($message, 422, ['errors' => $errors]);
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $message = 'Resource not found'): JSONResponse
    {
        return self::error($message, 404);
    }

    /**
     * Create an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JSONResponse
    {
        return self::error($message, 401);
    }

    /**
     * Create a forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JSONResponse
    {
        return self::error($message, 403);
    }

    /**
     * Create a paginated response
     */
    public static function paginated($data, int $total, int $limit, int $offset): JSONResponse
    {
        return self::success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'next_offset' => ($offset + $limit < $total) ? $offset + $limit : null
            ]
        ]);
    }
}