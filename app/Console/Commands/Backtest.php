<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Backtest extends Command
{
    protected $ticks = [], $events = [], $openbooks = [],
        $donebooks = [], $baseAmount = 0, $quoteAmount = 0,
        $startAmount = 0;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backtest:run';

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
    public function handle()
    {
        $pair = 'trx_idr';
        $this->startAmount = 1000000;
        $this->baseAmount = 0;
        $this->quoteAmount = $this->startAmount;
        $upperLimitPrice = 2008;
        $lowerLimitPrice = 1230;

        $backtestData = $this->_getBacktestData($pair, '-30 days', '2021-05-27 18:00:00');
        if (empty($backtestData)) {
            echo "No DATA";
            return 0;
        }

        foreach ($backtestData['t'] as $index => $time) {
            while (!empty($this->events)) {
                $eventData = array_shift($this->events);
                switch ($eventData['event']) {
                    case 'tick':
                        $this->_tick($eventData);
                        break;
                    case 'order':
                        $this->_order($time, $eventData);
                        break;
                    case 'fill':
                        $this->_fill($time, $eventData);
                        break;
                }
            }

            $tickData = [
                'eventId' => (string) Str::uuid(),
                'event' => 'tick',
                'oPrice' => $backtestData['o'][$index],
                'hPrice' => $backtestData['h'][$index],
                'lPrice' => $backtestData['l'][$index],
                'cPrice' => $backtestData['c'][$index],
                'volume' => $backtestData['v'][$index],
                'time' => $backtestData['t'][$index]
            ];

            $this->events[] = $tickData;
            $this->_executeStrategy($tickData);
        }

        $this->table(['Info', 'Value'], [
            ['Quote Assets', str_pad(number_format($this->quoteAmount, 0, '', ''), 12, " ", STR_PAD_LEFT)],
            ['Base Assets', str_pad(number_format($this->baseAmount, 0, '', ''), 12, " ", STR_PAD_LEFT)]
        ]);
    }

    private function _executeStrategy($tickData) {
        if ($this->baseAmount > 0) {
            $buyOrderPrice = 0;
            foreach ($this->donebooks as $book) {
                if ($book['type'] === 'buy') {
                    $buyOrderPrice = $book['price'];
                }
            }

            if ($buyOrderPrice > 0) {
                $this->events[] = [
                    'eventId' => (string) Str::uuid(),
                    'event' => 'order',
                    'type' => 'sell',
                    'price' => ceil($buyOrderPrice + ($buyOrderPrice * 0.1)),
                    'amount' => $this->baseAmount
                ];
            }
        } else {
            $targetAmount = $this->startAmount + ($this->startAmount * 1);
            if ($this->quoteAmount > 0  && $this->quoteAmount < $targetAmount) {
                $this->events[] = [
                    'eventId' => (string) Str::uuid(),
                    'event' => 'order',
                    'type' => 'buy',
                    'price' => $tickData['lPrice'],
                    'amount' => $this->quoteAmount
                ];
            }
        }
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
        }
    }

    private function _tick($eventData) {
        // echo 'Tick ' . date('d-m-Y H:i:s', $eventData['time']) . ': ' . $eventData['cPrice'] . "\n";

        foreach ($this->openbooks as $index => $order) {
            if ($order['type'] === 'sell') {
                if ($eventData['cPrice'] > $order['price']) {
                    $this->events[] = [
                        'eventId' => (string) Str::uuid(),
                        'event' => 'fill',
                        'type' => 'sell',
                        'price' => $order['price'],
                        'amount' => $order['amount']
                    ];

                    unset($this->openbooks[$index]);
                }
            } else if ($order['type'] === 'buy') {
                if ($eventData['cPrice'] < $order['price']) {
                    $this->events[] = [
                        'eventId' => (string) Str::uuid(),
                        'event' => 'fill',
                        'type' => 'buy',
                        'price' => $order['price'],
                        'amount' => $order['amount']
                    ];

                    unset($this->openbooks[$index]);
                }
            }
        }

        $this->ticks[] = $eventData;
    }

    private function _getBacktestData($pair, $from, $to) {
        $tf = 1;
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
            if (!isset($backtestData['s']) || $backtestData['s'] != 'ok') {
                return [];
            }

            Cache::put('bd_' . $pair . $from . $to, $backtestData);
        }
        
        return $backtestData;
    }
}
