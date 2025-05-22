<?php
namespace App\Handlers;

use App\Helpers\ResponseHelper;

class ErrorHandler {
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        $this->logError("Error: $errstr in $errfile on line $errline");
        
        ResponseHelper::jsonResponse([
            'success' => false,
            'error' => 'An error occurred',
            'code' => 'INTERNAL_ERROR'
        ], 500);
        
        return true;
    }
    
    public function handleException(\Throwable $exception): void {
        $this->logError("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        $statusCode = method_exists($exception, 'getCode') ? $exception->getCode() : 500;
        if ($statusCode < 100 || $statusCode > 599) {
            $statusCode = 500;
        }
        
        $response = [
            'success' => false,
            'error' => $exception->getMessage(),
            'code' => method_exists($exception, 'getErrorCode') ? $exception->getErrorCode() : 'INTERNAL_ERROR'
        ];
        
        if ($exception instanceof \App\Exceptions\ValidationException) {
            $response['errors'] = $exception->getErrors();
        }
        
        ResponseHelper::jsonResponse($response, $statusCode);
    }
    
    private function logError(string $message): void {
        error_log($message);
        
        $logDir = __DIR__ . '/../../storage/logs';
        $logFile = $logDir . '/error.log';
        
        // Create directory if it doesn't exist
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                error_log("Failed to create log directory: $logDir");
                return;
            }
        }
        
        // Write to log file
        $timestamp = date('[Y-m-d H:i:s]');
        $result = file_put_contents($logFile, "$timestamp $message\n", FILE_APPEND);
        
        if ($result === false) {
            error_log("Failed to write to log file: $logFile");
        }
    }
}