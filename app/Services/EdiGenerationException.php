<?php

namespace App\Services;

use RuntimeException;

class EdiGenerationException extends RuntimeException
{
    public function __construct(
        string $message,
        private array $blockingErrors,
        private array $controls
    ) {
        parent::__construct($message);
    }

    public function blockingErrors(): array
    {
        return $this->blockingErrors;
    }

    public function controls(): array
    {
        return $this->controls;
    }
}
