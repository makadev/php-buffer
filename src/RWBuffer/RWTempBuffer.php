<?php declare(strict_types=1);

namespace makadev\Buffer\RWBuffer;

use makadev\Buffer\Exceptions\FatalException;

class RWTempBuffer extends RWResourceBuffer {
    /**
     * Create Temp Buffer (using php://temp schema) with given Size
     *
     * @param int $size
     * @param int $maxMemorySize maximum size to keep resource in memory using php://temp/maxmemory:NN
     */
    function __construct(int $size, int $maxMemorySize = 2 * (1024 * 1024)) {
        if($size < $maxMemorySize) {
            $maxMemorySize = $size;
        }
        $resource = fopen('php://temp/maxmemory:' . $maxMemorySize, 'r+b');
        if ($resource === false) {
            // @codeCoverageIgnoreStart
            // hard to test, but possible
            throw new FatalException("resource creation failed");
            // @codeCoverageIgnoreEnd
        }
        parent::__construct($size, $resource);
    }
}