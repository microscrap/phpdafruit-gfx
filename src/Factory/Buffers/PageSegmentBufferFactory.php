<?php

namespace Microscrap\GFX\PhpdaFruit\Factory\Buffers;

use Exception;
use Microscrap\GFX\PhpdaFruit\Buffers\PageSegmentBuffer;
use ScrapyardIO\NutsAndBolts\Buffers\Factory\FSBFFactory;

class PageSegmentBufferFactory extends FSBFFactory
{
    /**
     * @throws Exception
     */
    public function build(): PageSegmentBuffer
    {
        return new PageSegmentBuffer(
            $this->width,
            $this->height,
            $this->buildFormatSpec()
        );
    }
}
