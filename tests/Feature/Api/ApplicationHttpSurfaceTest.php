<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApplicationHttpSurfaceTest extends TestCase
{
    public function test_root_path_is_not_exposed_by_the_api_application(): void
    {
        $this->get('/')->assertNotFound();
    }
}
