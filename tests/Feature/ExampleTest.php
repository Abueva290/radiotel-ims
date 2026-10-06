<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page has no content of its own; it sends visitors to the login page.
     */
    public function test_home_page_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}