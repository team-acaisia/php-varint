<?php

declare(strict_types=1);

namespace Acaisia\Multiformats\Varint;

use Acaisia\ByteArray\ByteArray;
use Acaisia\Multiformats\Varint\Exception\NotMinimalException;
use Acaisia\Multiformats\Varint\Exception\OverflowException;
use Acaisia\Multiformats\Varint\Exception\UnderflowException;
use Brick\Math\BigInteger;

class Varint
{
    public const MaxLenUvarint63 = 9;
    public const MaxValueUvarint63 = '9223372036854775807'; //(1 << 63) - 1

    private BigInteger $value;

    private function __construct($value)
    {
        $this->value = BigInteger::of($value);
        if ($this->value->isGreaterThan(BigInteger::of(self::MaxValueUvarint63))) {
            throw new OverflowException();
        }
    }

    public static function fromBigInteger(BigInteger $bigInteger): self
    {
        return new self($bigInteger);
    }

    public static function fromInteger(int $int): self
    {
        return new self($int);
    }

    /**
     * Return the size (in bytes) of `$integer` encoded as an unsigned varint.
     *
     * This may return a size greater than MaxUvarintLen63, which would be an
     * illegal value, and would be rejected by readers.
     */
    public static function uvarintSize(BigInteger $integer): int
    {
        $bits = $integer->getBitLength();

        $q = $bits/7;
        $r = $bits%7;
        $size = $q;
        if ($r > 0 || $size == 0) {
            $size++;
        }

        return (int) $size;
    }

    /**
     * This function packs a uint64 (BigInteger in this object) into a ByteArray
     */
    public function toByteArray(): ByteArray
    {
        $x = clone $this->value;
        $buf = [];
        $i = 0;
        while ($x->compareTo(0x80) >= 0) {
            $buf[$i] = $x->and(0xFF)->or(0x80)->toInt();
            $x = $x->shiftedRight(7);
            $i++;
        }
        $buf[$i] = $x->and(0xFF)->toInt();
        return ByteArray::fromArray($buf);
    }

    /**
     * Reads an unsigned varint from the beginning of $buffer, and sets the varint on itself
     */
    public static function fromByteArray(ByteArray $buffer): Varint
    {
        $x = BigInteger::zero();
        $s = 0;
        foreach ($buffer->toArray() as $i => $b) {
            if ($i == self::MaxLenUvarint63-1 && $b >= 0x80 || $i >= self::MaxLenUvarint63) {
                // this is the 9th and last byte we're willing to read, but it
                // signals there's more (1 in MSB).
                // or this is the >= 10th byte, and for some reason we're still here.
                throw new OverflowException();
            }
            if ($b < 0x80) {
                if ($b == 0 && $s > 0) {
                    throw new NotMinimalException();
                }

                return Varint::fromBigInteger($x->or(BigInteger::of($b)->shiftedLeft($s)));
            }

            $x = $x->or(BigInteger::of($b)->and(0x7f)->shiftedLeft($s));
            $s += 7;
        }

        throw new UnderflowException();
    }

    public function getValue(): BigInteger
    {
        return $this->value;
    }
}
