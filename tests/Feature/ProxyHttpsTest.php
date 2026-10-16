<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProxyHttpsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['trustedproxy.proxies' => ['172.20.0.1']]);
        Route::get('/proxy-https-check', fn () => [
            'secure' => request()->isSecure(),
            'url' => route('attendances.index'),
        ]);
    }

    public function test_configured_proxy_generates_https_urls(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/proxy-https-check')
            ->assertOk()->assertJson(['secure' => true, 'url' => 'https://localhost/attendances']);
    }

    public function test_untrusted_client_cannot_spoof_https(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/proxy-https-check')
            ->assertOk()->assertJson(['secure' => false, 'url' => 'http://localhost/attendances']);
    }

    public function test_local_http_still_works(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.20.0.1'])
            ->get('http://localhost/proxy-https-check')
            ->assertOk()->assertJson(['secure' => false, 'url' => 'http://localhost/attendances']);
    }
}
