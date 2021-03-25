<?php declare(strict_types=1);

namespace makadev\Buffer\RWBuffer;

use makadev\Buffer\Exceptions\FatalException;

class RWMemoryBuffer extends RWResourceBuffer {
    /**
     * Create Buffer (using php://memory schema) with given Size
     *
     * @param int $size
     */
    function __construct(int $size) {
        $memory = fopen('php://memory', "r+b");
        if ($memory === false) {
            // @codeCoverageIgnoreStart
            // hard to test, but possible
            throw new FatalException("resource creation failed");
            // @codeCoverageIgnoreEnd
        }
        parent::__construct($size, $memory);
        // preallocate and use a zero block of at least 1 MB to reduce the
        // performance penalty on larger allocs
        $this->clearBufferSize = 1024 * 1024;
        $this->clearBuffer($size);
    }

    /**
     * Explicitly Release the internal Buffer, obviously reads/writes will fail after that
     *
     * @return void
     */
    function release(): void {
        if ($this->bufferResource === null) return;
        $this->bufferResource = null;
    }
}