<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Blade;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests don't need built front-end assets.
        $this->withoutVite();

        // Flux Pro is a paid package. When it isn't installed (for example in CI or a
        // fresh cloud checkout) register plain stand-ins for its components so pages
        // still render in tests. With Flux Pro installed this does nothing.
        if (! class_exists(\FluxPro\FluxProServiceProvider::class)) {
            Blade::anonymousComponentPath(__DIR__.'/Support/flux-pro-stubs', 'flux');
        }
    }
}
