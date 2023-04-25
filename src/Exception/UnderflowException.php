<?php

declare(strict_types=1);

namespace Acaisia\Multiformats\Varint\Exception;

class UnderflowException extends InvalidVarintException {
    protected $message = 'varints malformed, could not reach the end';
}
