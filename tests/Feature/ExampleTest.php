<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Vwajèn');
    }
}
