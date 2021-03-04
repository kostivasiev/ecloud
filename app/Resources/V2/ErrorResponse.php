<?php

namespace App\Resources\V2;

/**
 * Class ErrorResponse
 * @package App\Resources\V2
 * @property string $title
 * @property string $message
 * @property string $status
 */
class ErrorResponse
{
    public static function create(?string $title = '', ?string $message = '', ?int $status = 200)
    {
        return response()->json([
            'errors' => [
                'title' => $title,
                'details' => $message,
                'status' => $status,
            ]
        ])->setStatusCode($status);
    }
}