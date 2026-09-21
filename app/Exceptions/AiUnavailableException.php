<?php

namespace App\Exceptions;

use RuntimeException;

class AiUnavailableException extends RuntimeException
{
    /**
     * True when Google reported it is overloaded or rate limiting (HTTP 429/503).
     */
    public function isBusy(): bool
    {
        return in_array($this->getCode(), [429, 503], true);
    }
}
