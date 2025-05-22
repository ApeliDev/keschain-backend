<?php
namespace App\Helpers;

class ResponseHelper {
    public static function jsonResponse(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
    
    public static function successResponse(string $message = '', array $data = []): array {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }
    
    public static function errorResponse(string $error, string $code = '', array $details = []): array {
        return [
            'success' => false,
            'error' => $error,
            'code' => $code,
            'details' => $details,
            'timestamp' => date('c')
        ];
    }
}