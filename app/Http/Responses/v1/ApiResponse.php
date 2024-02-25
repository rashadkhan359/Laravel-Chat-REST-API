<?php

namespace App\Http\Responses\v1;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    private $data;
    private $message;
    private $statusCode;

    public function __construct($data = null, string $message = '', int $statusCode = Response::HTTP_OK)
    {
        $this->data = $data;
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    public function respond(): JsonResponse
    {
        $response = [
            'data' => $this->data,
            'message' => $this->message,
        ];

        return response()->json($response, $this->statusCode);
    }

    public static function success($data = null, string $message = '', int $statusCode = Response::HTTP_OK): ApiResponse
    {
        return new ApiResponse($data, $message, $statusCode);
    }

    public static function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): ApiResponse
    {
        return new ApiResponse(null, $message, $statusCode);
    }

    public static function noContent(string $message = '', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json($message, $statusCode);
    }

    // You can add additional methods for different types of responses (e.g., created, updated)

    // Getters and setters for message and data can also be added
}
