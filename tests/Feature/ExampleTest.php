<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_calendar(): void
    {
        $this->get('/')->assertRedirect('/calendar');
    }
}

test('search engines are kept out', function () {
    expect(file_get_contents(public_path('robots.txt')))->toContain("Disallow: /\n");

    $this->get(route('auth.magic-link.form'))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
