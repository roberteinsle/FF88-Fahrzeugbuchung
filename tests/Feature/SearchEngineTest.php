<?php

test('search engines are kept out', function () {
    expect(file_get_contents(public_path('robots.txt')))->toContain("Disallow: /\n");

    $this->get(route('auth.magic-link.form'))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
