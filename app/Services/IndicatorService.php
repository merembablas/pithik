<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IndicatorService
{
    public function __construct()
    {
        ini_set('trader.real_precision', '2');
    }

    public function macd($data, $params)
    {

    }

    public function rsi($periode, $iddx)
    {
        $pairs = $iddx->pairs();

        $rsi15m = [];
        $rsi1h = [];
        $rsi4h = [];
        $rsiBNC15m = [];
        $rsiBNC1h = [];
        $rsiBNC4h = [];
        foreach ($pairs as $pair) {
            foreach ([ 900, 3600, 14400 ] as $time) {
                $prices = DB::select( DB::raw('SELECT * FROM tickers WHERE id in (SELECT MAX(id) as max_id FROM tickers 
                    WHERE timeframe="1m" AND pair="' . $pair . '" GROUP BY UNIX_TIMESTAMP(created_at) DIV ' . $time . ') ORDER BY created_at DESC LIMIT 100'));

                if (count($prices) >= $periode) {
                    $data = [];
                    $dataBNC = [];
                    foreach ($prices as $price) {
                        $data[] = $price->price_iddx;
                        $dataBNC[] = $price->price_bnc;
                    }

                    $dataBNC = array_reverse($dataBNC);
                    $pointsBNC = trader_rsi($dataBNC, $periode);
                    $lastPointBNC = round(array_pop($pointsBNC));

                    $data = array_reverse($data);
                    $points = trader_rsi($data, $periode);
                    $lastPoint = round(array_pop($points));

                    if ($time == 900) {
                        $rsi15m[$pair] = $lastPoint;
                        $rsiBNC15m[$pair] = $lastPointBNC;
                    } else if ($time == 3600) {
                        $rsi1h[$pair] = $lastPoint;
                        $rsiBNC1h[$pair] = $lastPointBNC;
                    } else {
                        $rsi4h[$pair] = $lastPoint;
                        $rsiBNC4h[$pair] = $lastPointBNC;
                    }
                } else {
                    if ($time == 900) {
                        $rsi15m[$pair] = '-';
                        $rsiBNC15m[$pair] = '-';
                    } else if ($time == 3600) {
                        $rsi1h[$pair] = '-';
                        $rsiBNC1h[$pair] = '-';
                    } else {
                        $rsi4h[$pair] = '-';
                        $rsiBNC4h[$pair] = '-';
                    }
                }
            }
        }

        $data = [ 'iddx' => [], 'bnc' => [] ];
        foreach ($pairs as $pair) {
            $data['iddx'][] = [
                $pair,
                $rsi15m[$pair],
                $rsi1h[$pair],
                $rsi4h[$pair]
            ];

            $data['bnc'][] = [
                $pair,
                $rsiBNC15m[$pair],
                $rsiBNC1h[$pair],
                $rsiBNC4h[$pair]
            ];
        }

        return $data;
    }
}
