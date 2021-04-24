<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FixerService
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = config('app.fixer_api_url');
    }

    public function usdtoidr()
    {
        $IDR = Cache::get('usdtoidr');
        if (!$IDR) {
            $params = [ 'path' => '/latest?access_key=' . config('app.fixer_api_key') . '&symbols=IDR,USD' ];
            $data = $this->_send($params);

            $IDR = round((1 / $data['rates']['USD']) * $data['rates']['IDR']);
            Cache::put('usdtoidr', $IDR, $seconds = 3600 * 4);
        }

        return $IDR;
    }

    private function _send($params) {
        $response = Http::get($this->base_url . $params['path']);

        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Service to Fixer Failed: No Info');
        }
    }
}
