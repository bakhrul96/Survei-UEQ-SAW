<?php

return [
    'cookie_name' => env('SURVEY_COOKIE_NAME', 'ueq_survey_token'),
    'token_key' => env('SURVEY_TOKEN_KEY'),
    'submit_attempts_per_minute' => 10,
    'cookie_extra_days' => 7,
];
