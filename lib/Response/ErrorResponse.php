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
 * Standard error response
 */
class ErrorResponse extends JSONResponse
{
    /**
     * Create an error response
     */
    public function __construct(
        string $message,
        int $status = 400,
        ?string $code = null,
        ?array $errors = null,
        ?array $data = null
    ) {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($code !== null) {
            $response['code'] = $code;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        parent::__construct($response, $status);
    }

    /**
     * Create a validation error response
     */
    public static function validation(array $errors, string $message = 'Validation failed'): self
    {
        return new self($message, 422, 'validation_error', $errors);
    }

    /**
     * Create a not found error response
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self($message, 404, 'not_found');
    }

    /**
     * Create an unauthorized error response
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, 401, 'unauthorized');
    }

    /**
     * Create a forbidden error response
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403, 'forbidden');
    }

    /**
     * Create a conflict error response
     */
    public static function conflict(string $message = 'Conflict', ?array $data = null): self
    {
        return new self($message, 409, 'conflict', null, $data);
    }

    /**
     * Create an internal server error response
     */
    public static function internal(string $message = 'Internal server error'): self
    {
        return new self($message, 500, 'internal_error');
    }

    /**
     * Create a bad request error response
     */
    public static function badRequest(string $message = 'Bad request', ?array $errors = null): self
    {
        return new self($message, 400, 'bad_request', $errors);
    }

    /**
     * Create a rate limit error response
     */
    public static function rateLimit(string $message = 'Rate limit exceeded'): self
    {
        return new self($message, 429, 'rate_limit_exceeded');
    }

    /**
     * Create a service unavailable error response
     */
    public static function serviceUnavailable(string $message = 'Service unavailable'): self
    {
        return new self($message, 503, 'service_unavailable');
    }

    /**
     * Create a method not allowed error response
     */
    public static function methodNotAllowed(string $message = 'Method not allowed'): self
    {
        return new self($message, 405, 'method_not_allowed');
    }
}