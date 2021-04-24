<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CoinMarketCalService
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = config('app.coinmarketcal_api_url');
    }

    public function news()
    {
        $params = [ 'path' => '/events?max=75' ];
        $result = $this->_send($params);

        if ($result['status']['error_code'] != 0) {
            return [];
        }

        $news = [];
        foreach ($result['body'] as $item) {
            $category = isset($item['categories']) && is_array($item['categories']) ? $item['categories'][0]['name'] : '-';
            $news[] = [
                'coin' => $item['coins'][0]['symbol'],
                'date_event' => date('Y-m-d', strtotime($item['date_event'])),
                'category' => $category,
                'title' => $item['title']['en'],
                'proof' => $item['proof'],
                'source' => $item['source'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
        }

        $pageCount = $result['_metadata']['page_count'];

        if ($pageCount > 1) {
            foreach (range(2, $pageCount) as $pageIndex) {
                $params = [ 'path' => '/events?max=75&page=' . $pageIndex ];
                $result = $this->_send($params);

                if ($result['status']['error_code'] != 0) {
                    continue;
                }

                foreach ($result['body'] as $item) {
                    $news[] = [
                        'coin' => $item['coins'][0]['symbol'],
                        'date_event' => date('Y-m-d', strtotime($item['date_event'])),
                        'category' => $category,
                        'title' => $item['title']['en'],
                        'proof' => $item['proof'],
                        'source' => $item['source'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];
                }
            }
        }

        return $news;
    }

    private function _send($params) {
        $response = Http::withHeaders([
            'x-api-key' => config('app.coinmarketcal_api_key'),
            'Accept' => 'application/json'
        ])->get($this->base_url . $params['path']);

        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Service to CMC Failed: No Info');
        }
    }
}
