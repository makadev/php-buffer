<?php

use makadev\Buffer\Exceptions\FatalReadException;
use makadev\Buffer\Exceptions\FatalWriteException;
use makadev\Buffer\RWBuffer\RWTempBuffer;
use PHPUnit\Framework\TestCase;

class RWTempBufferTest extends TestCase {

    /**
     *
     */
    public function testNewBuffer(): void {
        $buffer = new RWTempBuffer(1024);
        // check size and initial position
        $this->assertEquals(1024, $buffer->getSize());
        $this->assertEquals(0, $buffer->getPosition());
    }

    /**
     *
     */
    public function testBufferWriteAfterRelease(): void {
        $buffer = new RWTempBuffer(1024);
        // check size and initial position
        $this->assertEquals(1024, $buffer->getSize());
        $this->assertEquals(0, $buffer->getPosition());

        $buffer->release();

        $this->expectException(FatalWriteException::class);

        $buffer->write("yay", 3);
    }

    /**
     *
     */
    public function testBufferReadAfterRelease(): void {
        $buffer = new RWTempBuffer(1024);
        // check size and initial position
        $this->assertEquals(1024, $buffer->getSize());
        $this->assertEquals(0, $buffer->getPosition());

        $buffer->release();

        $this->expectException(FatalReadException::class);

        $buffer->read(3);
    }
}
