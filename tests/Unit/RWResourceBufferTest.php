<?php

use makadev\Buffer\Exceptions\FatalReadException;
use makadev\Buffer\Exceptions\FatalWriteException;
use makadev\Buffer\RWBuffer\RWResourceBuffer;
use PHPUnit\Framework\TestCase;

class RWResourceBufferTest extends TestCase {

    /**
     *
     */
    public function testNewBuffer(): void {
        $res = fopen('php://temp', 'r+b');
        if($res === false) {
            throw new RuntimeException('Error preparing resource for test');
        }
        $buffer = new RWResourceBuffer(1024, $res);
        // check size and initial position
        $this->assertEquals(1024, $buffer->getSize());
        $this->assertEquals(0, $buffer->getPosition());
    }

    /**
     *
     */
    public function testBufferWriteAfterRelease(): void {
        $res = fopen('php://temp', 'r+b');
        if($res === false) {
            throw new RuntimeException('Error preparing resource for test');
        }
        $buffer = new RWResourceBuffer(1024, $res);
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
        $res = fopen('php://temp', 'r+b');
        if($res === false) {
            throw new RuntimeException('Error preparing resource for test');
        }
        $buffer = new RWResourceBuffer(1024, $res);
        // check size and initial position
        $this->assertEquals(1024, $buffer->getSize());
        $this->assertEquals(0, $buffer->getPosition());

        $buffer->release();

        $this->expectException(FatalReadException::class);

        $buffer->read(3);
    }
}
