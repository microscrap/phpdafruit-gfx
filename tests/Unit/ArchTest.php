<?php

arch('no debug statements leak into the package')
    ->expect(['dd', 'dump', 'var_dump', 'ray', 'print_r'])
    ->not->toBeUsed();

arch('buffers extend the format-spec framebuffer')
    ->expect('Microscrap\GFX\PhpdaFruit\Buffers')
    ->toExtend('ScrapyardIO\NutsAndBolts\Buffers\FormatSpecFrameBuffer');

arch('concerns are traits')
    ->expect('Microscrap\GFX\PhpdaFruit\Concerns')
    ->toBeTraits();
