<?php

test('root redirects guests to the welcome page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('welcome'));
});
