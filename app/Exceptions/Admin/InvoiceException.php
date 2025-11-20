<?php

namespace App\Exceptions\Admin;

use Exception;

class InvoiceException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
        $this->message = $message;
    }
}

