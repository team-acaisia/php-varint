<?php

declare(strict_types=1);

namespace Acaisia\Multiformats\Varint\Exception;

class NotMinimalException extends InvalidVarintException {
    protected $message = 'varint not minimally encoded';
}
