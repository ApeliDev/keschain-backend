<?php
namespace App\Exceptions;

class AuthException extends \Exception {
    protected $code = 401;
    protected $message = 'Authentication failed';
    
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
}