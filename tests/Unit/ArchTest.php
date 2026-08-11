<?php

arch('no debug statements leak into the package')
    ->expect('Microscrap\GFX\PhpdaFruit')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray', 'print_r']);

arch('the renderer extends the tubes 2D surface')
    ->expect('Microscrap\GFX\PhpdaFruit\PhpdafruitRenderer2D')
    ->toExtend('ScrapyardIO\Tubes\Rendering\Renderer2D');

it('keeps deprecated PhpdafruitGfx as a thin alias of PhpdafruitRenderer2D', function () {
    expect(class_exists(\Microscrap\GFX\PhpdaFruit\PhpdafruitGfx::class))->toBeTrue()
        ->and(is_subclass_of(
            \Microscrap\GFX\PhpdaFruit\PhpdafruitGfx::class,
            \Microscrap\GFX\PhpdaFruit\PhpdafruitRenderer2D::class,
        ))->toBeTrue();
});

arch('concerns are traits')
    ->expect('Microscrap\GFX\PhpdaFruit\Concerns')
    ->toBeTraits();
