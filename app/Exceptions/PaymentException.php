<?php
namespace App\Exceptions;

class PaymentException extends \Exception {
    protected $code = 400;
    protected $message = 'Payment processing failed';
    
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
    
    public static function insufficientBalance(): self {
        return new self('Insufficient balance for this transaction');
    }
    
    public static function invalidAmount(): self {
        return new self('Invalid amount specified');
    }
    
    public static function transactionFailed(string $reason): self {
        return new self("Transaction failed: $reason");
    }
}