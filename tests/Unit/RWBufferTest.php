<?php

use makadev\Buffer\Contract\RWBuffer;
use makadev\Buffer\RWBuffer\RWMemoryBuffer;
use makadev\Buffer\RWBuffer\RWTempBuffer;
use PHPUnit\Framework\TestCase;

class RWBufferTest extends TestCase {

    /**
     * Provide RWBuffer Implementations for testing
     *
     * @return array[]
     */
    public function implementationProvider(): array {
        return [
            [new RWMemoryBuffer(1024), 1024],
            [new RWMemoryBuffer(1024 * 1024), 1024 * 1024],
            [new RWTempBuffer(3333), 3333],
            [new RWTempBuffer(1024 * 1024), 1024 * 1024]
        ];
    }

    /**
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testNewBuffer(RWBuffer $implementation, int $size): void {
        // check size and initial position
        $this->assertEquals($size, $implementation->getSize());
        $this->assertEquals(0, $implementation->getPosition());

        // prepare test data to write
        $dashes = str_repeat("-", 100);
        $dots = str_repeat(".", 50);
        $pluses = str_repeat("+", 50);

        // write 100 dashes
        $implementation->write($dashes, 100);
        $this->assertEquals(100, $implementation->getPosition());
        // write 50 dots
        $implementation->write($dots, 50);
        $this->assertEquals(150, $implementation->getPosition());
        // seek to position 50 and write 50 plusses
        $implementation->setPosition(50);
        $this->assertEquals(50, $implementation->getPosition());
        $implementation->write($pluses, 50);
        $this->assertEquals(100, $implementation->getPosition());
        // seek to start and read 150 chars which should be each 50 dashes, pluses, dots
        $implementation->setPosition(0);
        $result = $implementation->read(150);
        $cmp = str_repeat("-", 50) . $pluses . $dots;
        $this->assertEquals($cmp, $result);
    }

    /**
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testRandomAccess(RWBuffer $implementation, int $size): void {
        // check size and initial position
        $this->assertEquals($size, $implementation->getSize());
        $this->assertEquals(0, $implementation->getPosition());

        // prepare test data to write
        $dashes = str_repeat("-", 100);

        // seek center
        $implementation->setPosition(intval($size / 2));
        // write 100 dashes
        $implementation->write($dashes, 100);
        $this->assertEquals(100 + intval($size / 2), $implementation->getPosition());
        // seek written part
        $implementation->setPosition(intval($size / 2));
        $result = $implementation->read(100);
        $this->assertEquals($dashes, $result);
    }

    /**
     * Read Before Write should return Zeroes
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testReadBeforeWrite(RWBuffer $implementation, int $size): void {
        // check size and initial position
        $this->assertEquals($size, $implementation->getSize());
        $this->assertEquals(0, $implementation->getPosition());

        //
        $zeroes = str_repeat("\0", 100);
        $result = $implementation->read(100);
        $this->assertEquals($zeroes, $result);
    }

    /**
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testSeekOutOfRange1(RWBuffer $implementation, int $size): void {
        $this->expectException(OutOfRangeException::class);
        $implementation->setPosition(-1);
    }

    /**
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testSeekOutOfRange2(RWBuffer $implementation, int $size): void {
        $this->expectException(OutOfRangeException::class);
        $implementation->setPosition($size + 1);
    }

    /**
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testSeekEnd(RWBuffer $implementation, int $size): void {
        $implementation->setPosition($size);
        $this->assertEquals($size, $implementation->getPosition());
        $str = $implementation->read(100);
        $this->assertEquals("", $str);
    }

    /**
     *
     * @dataProvider implementationProvider
     * @param RWBuffer $implementation
     * @param int $size
     */
    public function testBufferEnd(RWBuffer $implementation, int $size): void {

        // writing at end of buffer will only write as much as possible
        $implementation->setPosition($size - 50);
        $this->assertEquals($size - 50, $implementation->getPosition());
        $dashes = str_repeat("-", 100);
        $written = $implementation->write($dashes, 100);
        $this->assertEquals(50, $written);
        $this->assertEquals($size, $implementation->getPosition());

        // reading at end of buffer will only read as much as possible
        $implementation->setPosition($size - 50);
        $this->assertEquals($size - 50, $implementation->getPosition());
        $str = $implementation->read(100);
        $this->assertEquals(str_repeat("-", 50), $str);
        $this->assertEquals($size, $implementation->getPosition());
    }
}
