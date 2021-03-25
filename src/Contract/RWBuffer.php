<?php

namespace makadev\Buffer\Contract;

use makadev\Buffer\Exceptions\FatalReadException;
use makadev\Buffer\Exceptions\FatalWriteException;
use OutOfRangeException;

interface RWBuffer {

    /**
     * Return Buffer Size
     *
     * @return int
     */
    public function getSize(): int;

    /**
     * Return Buffer Pointer
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Set Buffer Pointer
     *
     * @param int $pos
     * @return void
     * @throws OutOfRangeException if position is not within the range [0...size]
     */
    function setPosition(int $pos): void;

    /**
     * Write Bytes from a String
     *
     * @param string $data
     * @param int $length number of bytes to write
     * @return int number of bytes written
     * @throws FatalWriteException if writing fails
     */
    public function write(string $data, int $length): int;

    /**
     * Read Bytes into a String
     *
     * @param int $length nr bytes to read from the buffer
     * @return string
     * @throws FatalReadException if reading fails
     */
    public function read(int $length): string;
}