<?php

declare(strict_types=1);

namespace Acaisia\Multiformats\Varint\Tests;

use Acaisia\ByteArray\ByteArray;
use Acaisia\Multiformats\Varint\Exception\NotMinimalException;
use Acaisia\Multiformats\Varint\Exception\OverflowException;
use Acaisia\Multiformats\Varint\Exception\UnderflowException;
use Acaisia\Multiformats\Varint\Varint;
use Brick\Math\BigInteger;

class VarintTest extends AbstractTestCase
{

    /**
     * @dataProvider provideVarintSize
     */
    public function testVarintSize(BigInteger $given)
    {
        $varint = Varint::fromBigInteger($given);

        // Check that the written bytes is actually the same size
        $this->assertSame($varint->toByteArray()->count(), Varint::uVarintSize($given));

        // Test a roundtrip of encoding
        $this->assertSame(
            (string) $given,
            (string) Varint::fromByteArray($varint->toByteArray())->getValue(),
        );
    }

    public static function provideVarintSize(): iterable
    {
        for ($i = 1; $i < 63; $i++) {
            yield [BigInteger::one()->shiftedLeft($i)];
        }
    }

    /**
     * @dataProvider provideToByteArray
     */
    public function testToByteArray(Varint $given, string $expectedHex)
    {
        $hexString = '';
        foreach ($given->toByteArray()->toArray() as $item) {
            $hexString .= bin2hex(chr($item));
        }
        $this->assertSame($expectedHex, $hexString);
        $this->assertSame($given->toByteArray()->count(), Varint::uVarintSize($given->getValue()));

        // Test a roundtrip of encoding
        $this->assertSame(
            (string) $given->getValue(),
            (string) Varint::fromByteArray($given->toByteArray())->getValue(),
        );
    }

    public static function provideToByteArray(): array
    {
        return [
            [Varint::fromInteger(1), '01'],
            [Varint::fromInteger(2), '02'],
            [Varint::fromInteger(127), '7f'],
            [Varint::fromInteger(128), '8001'],
            [Varint::fromInteger(255), 'ff01'],
            [Varint::fromInteger(256), '8002'],
            [Varint::fromInteger(300), 'ac02'],
            [Varint::fromInteger(16384), '808001'],
            [Varint::fromBigInteger(BigInteger::of('23498721394837387')), '8b8fa9ebe6fede29'],
            [Varint::fromBigInteger(BigInteger::of('9223372036854775807')), 'ffffffffffffffff7f'],
        ];
    }

    public function testOverflowFromBigInteger()
    {
        $this->expectException(OverflowException::class);
        Varint::fromBigInteger(BigInteger::of('9341239872134837387'));
    }

    public function testOverflow9thSignalsMore()
    {
        $buffer = ByteArray::fromArray([
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0xff,
        ]);
        $this->expectException(OverflowException::class);

        Varint::fromByteArray($buffer);
    }

    public function testOverflow()
    {
        $buffer = ByteArray::fromArray([
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0xff,
            0xff, 0xff, 0xff, 0x00,
        ]);
        $this->expectException(OverflowException::class);

        Varint::fromByteArray($buffer);
    }

    public function testNotMinimal()
    {
        $buffer = ByteArray::fromArray([
            0x81, 0x00,
        ]);
        $this->expectException(NotMinimalException::class);

        Varint::fromByteArray($buffer);
    }

    public function testUnderflow()
    {
        $buffer = ByteArray::fromArray([
            0x81, 0x81,
        ]);
        $this->expectException(UnderflowException::class);

        Varint::fromByteArray($buffer);
    }
}
