<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CoinMarketCapService
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = config('app.coinmarketcap_api_url');
    }

    public function tickerAll()
    {
        $data = Cache::get('cmc_data');
        if (!$data) {
            $params = [ 'path' => '/cryptocurrency/listings/latest' ];
            $result = $this->_send($params);

            if (!isset($result['data'])) {
                return [];
            }

            $data = [];
            foreach ($result['data'] as $coin) {
                $data[strtolower($coin['symbol'])] = $coin['quote']['USD']['price'];
            }

            Cache::put('cmc_data', $data, $seconds = 60 * 60 * 24);
        }

        return $data;
    }

    private function _send($params) {
        $cmcKey = Cache::get('cmcKey', 'coinmarketcap_api_key');
        Cache::put('cmcKey', $cmcKey == 'coinmarketcap_api_key' ? 'coinmarketcap2_api_key' : 'coinmarketcap_api_key');

        $response = Http::withHeaders([
            'X-CMC_PRO_API_KEY' => config('app.' . $cmcKey),
        ])->get($this->base_url . $params['path']);

        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Service to CMC Failed: No Info');
        }
    }
}
