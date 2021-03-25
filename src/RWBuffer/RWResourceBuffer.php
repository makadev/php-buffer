<?php declare(strict_types=1);

namespace makadev\Buffer\RWBuffer;

use makadev\Buffer\Contract\RWBuffer;
use makadev\Buffer\Exceptions\FatalReadException;
use makadev\Buffer\Exceptions\FatalWriteException;
use OutOfRangeException;

class RWResourceBuffer implements RWBuffer {
    /**
     * Buffer Resource
     *
     * @var resource|null
     */
    protected $bufferResource;

    /**
     * Maximum Size to Use
     *
     * @var int
     */
    protected $size;

    /**
     * Internal Pointer
     *
     * @var int
     */
    protected $ptr = 0;

    /**
     * Internal Point pointing after the highest written byte, used to check
     * if the buffer was initialized up to a certain point (especially for random access).
     *
     * @var int
     */
    protected $bufferEnd = 0;

    /**
     * Max Zeroed TempBuffer used for clearing the buffer
     * @var int
     */
    protected $clearBufferSize = 1024 * 8;

    /**
     * Create Buffer with given Size
     *
     * @param int $size
     * @param resource $res
     */
    public function __construct(int $size, $res) {
        $this->size = $size;
        $this->bufferResource = $res;
    }

    /**
     * Return Buffer Size
     *
     * @return int
     */
    public function getSize(): int {
        return $this->size;
    }

    /**
     * Return Buffer Pointer
     *
     * @return int
     */
    public function getPosition(): int {
        return $this->ptr;
    }

    /**
     * Set Buffer Pointer
     *
     * @param int $pos
     * @return void
     * @throws OutOfRangeException if position is not within the range [0...size]
     */
    public function setPosition(int $pos): void {
        // yes, we allow setting the internal pointer to $size which in effect says: buffer is full
        if ($pos > $this->size || $pos < 0) {
            throw new OutOfRangeException();
        }
        $this->ptr = $pos;
    }

    /**
     * zero out buffer on gap writes and read before write situations (random access)
     *
     * @param int $additional
     */
    protected function clearBuffer(int $additional = 0): void {
        $amount = ($this->ptr - $this->bufferEnd) + $additional;
        if ($this->bufferResource === null) {
            throw new FatalWriteException();
        }
        fseek($this->bufferResource, $this->bufferEnd, SEEK_SET);
        if ($amount > $this->clearBufferSize) {
            $zeros = str_repeat("\0", $this->clearBufferSize);
            $numWritten = 0;
            $numWrite = $amount;
            while ($numWrite > 0) {
                $iterwrite = $numWrite < $this->clearBufferSize ? $numWrite : $this->clearBufferSize;
                $iterwritten = fwrite($this->bufferResource, $zeros, $iterwrite);
                if ($iterwritten === false || ($iterwritten !== $iterwrite)) {
                    // @codeCoverageIgnoreStart
                    // hard to test, but possible
                    throw new FatalWriteException();
                    // @codeCoverageIgnoreEnd
                }
                $numWritten += $iterwritten;
                $numWrite -= $iterwritten;
            }
        } else {
            $numWritten = fwrite($this->bufferResource, str_repeat("\0", $amount), $amount);
        }
        if (($numWritten === false) || ($amount !== $numWritten)) {
            // @codeCoverageIgnoreStart
            // hard to test, but possible
            throw new FatalWriteException();
            // @codeCoverageIgnoreEnd
        }
        $this->bufferEnd += $numWritten;
    }

    /**
     * Write Bytes from a String
     *
     * @param string $data
     * @param int $length number of bytes to write
     * @return int number of bytes written
     * @throws FatalWriteException if writing fails
     */
    public function write(string $data, int $length): int {
        if ($this->bufferResource === null) {
            throw new FatalWriteException();
        }
        // check how much we can actually write before going out of bounds
        $canWrite = $this->size - $this->ptr;
        $numWrite = $canWrite < $length ? $canWrite : $length;
        // early exit if buffer is full or nothing to write
        if ($numWrite <= 0) return 0;
        // check if write would access behind buffer end
        if ($this->ptr > $this->bufferEnd) {
            $this->clearBuffer();
        }
        // reset file pointer
        fseek($this->bufferResource, $this->ptr, SEEK_SET);
        // write
        $numWritten = fwrite($this->bufferResource, $data, $numWrite);
        if ($numWritten === false) {
            // @codeCoverageIgnoreStart
            // hard to test, but possible
            throw new FatalWriteException();
            // @codeCoverageIgnoreEnd
        }
        $this->ptr += $numWritten;
        // fix bufferEnd if writing changed it
        if ($this->bufferEnd < ($this->ptr)) {
            $this->bufferEnd = $this->ptr;
        }
        return $numWritten;
    }

    /**
     * Read Bytes into a String
     *
     * @param int $length nr bytes to read from the buffer
     * @return string
     * @throws FatalReadException if reading fails
     */
    public function read(int $length): string {
        if ($this->bufferResource === null) {
            throw new FatalReadException();
        }
        // check how much we can actually read before going out of bounds
        $canRead = $this->size - $this->ptr;
        $numReading = $canRead < $length ? $canRead : $length;
        // early exit if ptr is at end of buffer or $length is 0
        if ($numReading <= 0) return "";
        // check if read (seek+read) would access behind buffer end
        if (($numReading + $this->ptr) > $this->bufferEnd) {
            $this->clearBuffer($numReading);
        }
        // reset file pointer
        fseek($this->bufferResource, $this->ptr, SEEK_SET);
        // write
        $result = fread($this->bufferResource, $numReading);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            // hard to test, but possible
            throw new FatalReadException();
            // @codeCoverageIgnoreEnd
        }
        $this->ptr += $numReading;
        return $result;
    }

    /**
     * Explicitly Release the internal Buffer, obviously reads/writes will fail after that
     *
     * @return void
     */
    public function release(): void {
        if ($this->bufferResource === null) return;
        fclose($this->bufferResource);
        $this->bufferResource = null;
    }
}