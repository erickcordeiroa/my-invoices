<?php

namespace App\Exceptions\Auth;

use Exception;

class ActivateException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
        $this->message = $message;
    }
}
