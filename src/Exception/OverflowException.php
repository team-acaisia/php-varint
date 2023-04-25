<?php

declare(strict_types=1);

namespace Acaisia\Multiformats\Varint\Exception;

class OverflowException extends InvalidVarintException {
    protected $message = 'varints larger than uint63 not supported';
}
