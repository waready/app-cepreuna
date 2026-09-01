<?php

namespace App\Exceptions;

use RuntimeException;

class BancoPreguntasApiException extends RuntimeException
{
    protected $status;

    protected $errors;

    public function __construct($message, $status = 503, array $errors = [])
    {
        parent::__construct($message);

        $this->status = (int) $status;
        $this->errors = $errors;
    }

    public function status()
    {
        return $this->status;
    }

    public function errors()
    {
        return $this->errors;
    }
}
