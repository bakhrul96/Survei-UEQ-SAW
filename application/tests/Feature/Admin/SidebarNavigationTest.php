<?php

use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
});

function sidebarNavigationLink(TestResponse $response, string $href): DOMElement
{
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($response->getContent());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $link = (new DOMXPath($document))->query(
        '//a[@data-flux-sidebar-item and @href="'.$href.'"]',
    )->item(0);

    expect($link)->toBeInstanceOf(DOMElement::class);

    return $link;
}

it('renders every working application area in workflow groups', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'Ikhtisar',
            'Dashboard',
            'Pengumpulan Data',
            'Pengaturan Studi',
            'Respons',
            'Laporan &amp; Ekspor',
            'Analisis',
            'Perhitungan',
            'Penilaian Teknis',
            'Akun',
            'Pengaturan Akun',
        ], false)
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation');

    foreach ([
        'admin.dashboard',
        'admin.study-settings',
        'admin.responses',
        'admin.reports',
        'admin.calculations',
        'admin.technical-assessments',
        'profile.edit',
    ] as $routeName) {
        sidebarNavigationLink($response, route($routeName));
    }
});

it('marks the dashboard destination as current', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

    expect(sidebarNavigationLink($response, route('admin.dashboard'))->hasAttribute('data-current'))->toBeTrue();
});

it('marks the account destination as current throughout account settings', function (string $routeName) {
    $response = $this->withSession(['auth.password_confirmed_at' => time()])
        ->actingAs($this->admin)
        ->get(route($routeName))
        ->assertOk();

    expect(sidebarNavigationLink($response, route('profile.edit'))->hasAttribute('data-current'))->toBeTrue();
})->with([
    'profile' => 'profile.edit',
    'security' => 'security.edit',
    'appearance' => 'appearance.edit',
]);
