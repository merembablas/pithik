<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class IndodaxService
{
    protected $private_base_url, $public_base_url, $rec_time;

    public function __construct()
    {
        $this->private_base_url = config('app.indodax_private_api_url');
        $this->public_base_url = config('app.indodax_public_api_url');
        $this->rec_time = 5000;
    }

    public function info()
    {
        $params = [ 'method' => 'getInfo' ];
        return $this->_sendPrivate($params);
    }

    public function transactions()
    {
        $params = [ 'method' => 'transHistory' ];
        return $this->_sendPrivate($params);
    }

    public function openOrders()
    {
        $params = [ 'method' => 'openOrders' ];
        return $this->_sendPrivate($params);
    }

    public function tickerAll()
    {
        $params = [ 'path' => '/ticker_all' ];
        $data = $this->_sendPublic($params);

        return $data['tickers'];
    }

    public function trade($pairs)
    {
        $data = [];
        foreach ($pairs as $pair) {
            $params = [ 'path' => '/trades/' . $pair ];
            $data[$pair] = $this->_sendPublic($params);
        }
        
        return $data;
    }

    public function depth($pair)
    {
        $params = [ 'path' => '/depth/' . $pair ];
        $data = $this->_sendPublic($params);
        
        return $data;
    }

    public function pairs()
    {
        $data = Cache::get('pairs');
        if (!$data) {
            $params = [ 'path' => '/pairs' ];
            $result = $this->_sendPublic($params);

            $data = [];
            foreach ($result as $item) {
                if ($item['base_currency'] != 'idr') continue;
                
                $data[] = $item['id'];
            }

            Cache::put('pairs', $data, $seconds = 3600 * 24);
        }

        return $data;
    }

    private function _sendPrivate($params) {
        $date = new \DateTime();
        $now = $date->getTimestamp() * 1000;
        $nextSeconds = $now + $this->rec_time;

        $data = [
	        'timestamp' => $now,
	        'recvWindow' => $nextSeconds
	    ] + $params;
        
        $postData = http_build_query($data, '', '&');
        $sign = hash_hmac('sha512', $postData, config('app.indodax_secret_key'));

        $response = Http::withHeaders([
            'Key' => config('app.indodax_api_key'),
            'Sign' => $sign,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->asForm()->post($this->private_base_url, $data);

        if ($response->successful()) {
            $result = $response->json();

            if ($result['success'] === 0) {
                throw new \Exception('Service to Indodax Failed: ' . $result['error']);
            }

            return $result['return'];
        } else {
            throw new \Exception('Service to Indodax Failed: No Info');
        }
    }

    private function _sendPublic($params) {
        $response = Http::get($this->public_base_url . $params['path']);

        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Service to Indodax Failed: No Info');
        }
    }
}
