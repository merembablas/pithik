<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\IndodaxService;

class Runner extends Command
{
    protected $events = [], $openbooks = [], $donebooks = [], $baseAmount = 0, $quoteAmount = 0;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'runner:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runner';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(IndodaxService $iddx)
    {
        $pair = 'trx_idr';
        $this->baseAmount = 0;
        $startAmount = 1000000;
        $this->quoteAmount = $startAmount;
        $upperLimitPrice = 2008;
        $lowerLimitPrice = 1230;


        $this->events = [
            [ 'event' => 'opening' ]
        ];
        
        $backtestData = $this->_getBacktestData($pair, '-60 days', '2021-05-21 19:00:00');
        foreach ($backtestData['t'] as $index => $time) {
            while (!empty($this->events)) {
                $eventData = array_shift($this->events);
                switch ($eventData['event']) {
                    case 'tick':
                        $this->_tick($eventData);
                        break;
                    case 'opening':
                        $this->_opening($time, $eventData);
                        break;
                    case 'order':
                        $this->_order($time, $eventData);
                        break;
                    case 'fill':
                        $this->_fill($time, $eventData);
                        break;
                }
            }


            $this->events = [
                [ 'event' => 'tick', 'price' => $backtestData['c'][$index], 'time' => $backtestData['t'][$index] ]
            ];
        }

        $this->table(['Info', 'Value'], [
            ['Quote Assets', str_pad(number_format($this->quoteAmount, 0, ',', '.'), 12, " ", STR_PAD_LEFT)],
            ['Base Assets', str_pad($this->baseAmount, 12, " ", STR_PAD_LEFT)],
            ['Profit', str_pad(number_format($this->quoteAmount - $startAmount, 0, ',', '.'), 12, " ", STR_PAD_LEFT)]
        ]);

        die();


        $tickers = $iddx->tickerAll();
        $lastPrice = 0;
        foreach ($tickers as $k => $tick) {
            if ($k === $pair) {
                $lastPrice = $tick['last'];
                break;
            }
        }

        $depth = $iddx->depth(str_replace('_', '', $pair));
        $data = $this->_transformDepth($lastPrice, $depth);
        $this->table(['Market', 'Amount'], $data);
    }

    private function _opening($time, $eventData) {
        echo date('d-m-Y H:i:s', $time) . " Event Opening\n";
        $this->events[] = [
            'event' => 'order',
            'type' => 'buy',
            'price' => 1000,
            'amount' => 1000000
        ];
    }

    private function _middlegame() {

    }

    private function _endgame() {

    }

    private function _order($time, $eventData) {
        echo date('d-m-Y H:i:s', $time) . " Event Order: " . $eventData['type'] . ", " . $eventData['price'] . "\n";

        $this->openbooks[] = [
            'type' => $eventData['type'],
            'price' => $eventData['price'],
            'amount' => $eventData['amount']
        ];

        if ($eventData['type'] === 'buy') {
            $this->quoteAmount = $this->quoteAmount - $eventData['amount'];
        } else if ($eventData['type'] === 'sell') {
            $this->baseAmount = $this->baseAmount - $eventData['amount'];
        }
    }

    private function _fill($time, $eventData) {
        echo date('d-m-Y H:i:s', $time) . " Event Fill: " . $eventData['type'] . ", " . $eventData['price'] . "\n";

        $this->donebooks[] = [
            'type' => $eventData['type'],
            'price' => $eventData['price'],
            'amount' => $eventData['amount']
        ];

        if ($eventData['type'] === 'sell') {
            $quoteAmount = $eventData['amount'] * $eventData['price'];
            $this->quoteAmount = $this->quoteAmount + $quoteAmount;
        } else if ($eventData['type'] === 'buy') {
            $baseAmount = $eventData['amount'] / $eventData['price'];
            $this->baseAmount = $this->baseAmount + $baseAmount;

            $this->events[] = [
                'event' => 'order',
                'type' => 'sell',
                'price' => $eventData['price'] + ($eventData['price'] * 0.9),
                'amount' => $baseAmount
            ];
        }
    }

    private function _tick($eventData) {
        //echo 'Tick ' . date('d-m-Y H:i:s', $eventData['time']) . ': ' . $eventData['price'] . "\n";

        foreach ($this->openbooks as $index => $order) {
            if ($order['type'] === 'sell') {
                if ($eventData['price'] > $order['price']) {
                    $this->events[] = [
                        'event' => 'fill',
                        'type' => 'sell',
                        'price' => $order['price'],
                        'amount' => $order['amount']
                    ];

                    unset($this->openbooks[$index]);
                }
            } else if ($order['type'] === 'buy') {
                if ($eventData['price'] < $order['price']) {
                    $this->events[] = [
                        'event' => 'fill',
                        'type' => 'buy',
                        'price' => $order['price'],
                        'amount' => $order['amount']
                    ];

                    unset($this->openbooks[$index]);
                }
            }
        }

    }

    private function _transformDepth($lastPrice, $data) {
        $bids = [];
        $asks = [];
        foreach ($data['buy'] as $item) {
            $bids[$item[0]] = round($item[0] * $item[1]);
        }

        foreach ($data['sell'] as $item) {
            $asks[$item[0]] = round($item[0] * $item[1]);
        }

        arsort($bids);
        $bestBids = array_slice($bids, 0, 5, true);

        arsort($asks);
        $bestAsks = array_slice($asks, 0, 5, true);

        $orderBooks = $bestBids + [ $lastPrice => 'Current' ] +  $bestAsks;
        krsort($orderBooks, SORT_NUMERIC);

        $oBData = [];
        foreach ($orderBooks as $k => $v) {
            $oBData[] = [ $k, $v ];          
        }

        return $oBData;
    }

    private function _getBacktestData($pair, $from, $to) {
        $tf = 15;
        $start = strtotime($from);
        $end = strtotime($to);
        $pair = strtoupper(str_replace('_', '', $pair));

        $backtestData = Cache::get('bd_' . $pair . $from . $to);
        if (!$backtestData) {
            $response = Http::get('https://indodax.com/tradingview/history?symbol='.$pair.'&resolution='.$tf.'&from='.$start.'&to='.$end);

            if (!$response->successful()) {
                return [];
            }

            $backtestData = $response->json();
            if (!isset($data['s']) || $data['s'] != 'ok') {
                return [];
            }

            Cache::put('bd_' . $pair . $from . $to, $backtestData);
        }
        
        
        return $backtestData;
    }
}
