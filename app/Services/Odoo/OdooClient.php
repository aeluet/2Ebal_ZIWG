<?php

namespace App\Services\Odoo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OdooClient
{
    // Odoo-ren JSON-2 API-ari dei bat egiten dio: POST {url}/json/2/{model}/{method}
    // $params: metodoaren parametroak (domain, fields, limit, ids...).
    public function call(string $model, string $method, array $params = []): array
    {
        try {
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
        } catch (RequestException $e) {
            // Odoo-k erantzun du, baina errore batekin (401, 404, 500...).
            $mezua = $e->response->json('message') ?? $e->getMessage();
            throw new OdooException($mezua, $e->response->status());
        } catch (ConnectionException $e) {
            // Odoo-k ez du erantzun (geldituta, sare-errorea, timeout...).
            throw new OdooException('Ezin da Odoo-rekin konektatu: '.$e->getMessage());
        }
    }

    // Erregistroak bilatu eta irakurri, dei bakarrean.
    public function searchRead(string $model, array $domain, array $fields, int $limit = 50): array
    {
        return $this->call($model, 'search_read', [
            'domain' => $domain,
            'fields' => $fields,
            'limit' => $limit,
        ]);
    }

    // Erregistro bat sortu eta bere id-a itzuli.
    // Odoo-ren create-ek erregistro-zerrenda bat jasotzen du (vals_list) eta id-en zerrenda itzultzen du.
    public function create(string $model, array $vals): int
    {
        $ids = $this->call($model, 'create', ['vals_list' => [$vals]]);

        return $ids[0];
    }
}
