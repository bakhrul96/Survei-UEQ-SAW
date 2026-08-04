<?php

it('does not expose public registration', function () {
    $this->get('/register')->assertNotFound();
});
