<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Http;

class OdooClient
{
    // Odoo-ren JSON-2 API-ari dei bat egiten dio: POST {url}/json/2/{model}/{method}
    // $params: metodoaren parametroak (domain, fields, limit, ids...).
    public function call(string $model, string $method, array $params = []): array
    {
        return Http::baseUrl(config('services.odoo.url'))
            ->withToken(config('services.odoo.key'))
            ->withHeaders([
                'X-Odoo-Database' => config('services.odoo.db'),
                'User-Agent' => 'antzokia-laravel',
            ])
            ->timeout(10)
            ->post("/json/2/{$model}/{$method}", $params)
            ->throw()
            ->json();
    }
}
