<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BinanceService
{
    protected $private_base_url, $public_base_url;

    public function __construct()
    {
        $this->private_base_url = config('app.binance_private_api_url');
        $this->public_base_url = config('app.binance_public_api_url');
    }

    public function tickerAll()
    {
        $params = [ 'path' => '/ticker/price' ];
        $result = $this->_sendPublic($params);

        $data = [];
        foreach ($result as $item) {
            $data[strtolower($item['symbol'])] = $item['price'];
        }

        return $data;
    }

    private function _sendPublic($params) {
        $response = Http::get($this->public_base_url . $params['path']);

        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Service to Binance Failed: No Info');
        }
    }
}
