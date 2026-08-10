<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

it('uses cached configuration to force the public HTTPS root', function () {
    $previousEnvironment = getenv('FORCE_PUBLIC_URL');
    $previousConfig = config('app.force_public_url');
    $previousUrl = config('app.url');

    try {
        putenv('FORCE_PUBLIC_URL=false');
        $_ENV['FORCE_PUBLIC_URL'] = 'false';
        $_SERVER['FORCE_PUBLIC_URL'] = 'false';

        URL::forceRootUrl(null);
        URL::forceScheme(null);
        config()->set([
            'app.force_public_url' => true,
            'app.url' => 'https://survei.example.test',
        ]);

        (new AppServiceProvider(app()))->boot();

        expect(URL::to('/livewire-test'))->toBe('https://survei.example.test/livewire-test');
    } finally {
        if ($previousEnvironment === false) {
            putenv('FORCE_PUBLIC_URL');
            unset($_ENV['FORCE_PUBLIC_URL'], $_SERVER['FORCE_PUBLIC_URL']);
        } else {
            putenv('FORCE_PUBLIC_URL='.$previousEnvironment);
            $_ENV['FORCE_PUBLIC_URL'] = $previousEnvironment;
            $_SERVER['FORCE_PUBLIC_URL'] = $previousEnvironment;
        }

        config()->set([
            'app.force_public_url' => $previousConfig,
            'app.url' => $previousUrl,
        ]);
        URL::forceRootUrl(null);
        URL::forceScheme(null);
    }
});
