<?php

test('guest users are redirected to home from the root route', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('home'));
});
