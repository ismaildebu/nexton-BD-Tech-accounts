<?php

it('returns a successful response', function () {
    $response = $this->get('/');
    $response->assertStatus(302);
});

it('login page loads successfully', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});