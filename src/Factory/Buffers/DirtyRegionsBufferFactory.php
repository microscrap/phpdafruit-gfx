<?php

namespace Microscrap\GFX\PhpdaFruit\Factory\Buffers;

use Exception;
use Microscrap\GFX\PhpdaFruit\Buffers\DirtyRegionsBuffer;
use ScrapyardIO\NutsAndBolts\Buffers\Factory\FSBFFactory;

class DirtyRegionsBufferFactory extends FSBFFactory
{
    /**
     * @throws Exception
     */
    public function build(): DirtyRegionsBuffer
    {
        return new DirtyRegionsBuffer(
            $this->width,
            $this->height,
            $this->buildFormatSpec()
        );
    }
}
