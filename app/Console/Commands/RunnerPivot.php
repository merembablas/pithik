<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\IndodaxService;
use Telegram\Bot\Api;
use Symfony\Component\Console\Output\BufferedOutput;

class RunnerPivot extends Command
{
    protected $iddx, $tick, $pivot, $rsi, $sma, $pair, $base, $quote, $settings, $info, $trades;
    protected $resolutionRSI = [ 15 => '-90 hours', 60 => '-15 days', 240 => '-60 days', 'D' => '-360 days' ];
    protected $resolutionPivot = [ 15 => '-30 minutes', 60 => '-2 hours', 240 => '-8 hours', 'D' => '-2 days' ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pivot:run {pair}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pivot Runner';

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
        $bots = DB::table('bots')
            ->where('type', 'pivot')
            ->where('pair', $this->argument('pair'))
            ->where('status', 'active')->get();

        if (count($bots) === 0) {
            return 0;
        }

        $this->startedAt = strtotime($bots[0]->started_at);
        $this->settings = json_decode($bots[0]->settings, true);
        $this->corrOrders = json_decode($bots[0]->corresponding_orders, true);

        $output = new BufferedOutput;
        $this->output = $output;
        $isSendMessage = false;
        $messages = [];

        $this->iddx = $iddx;
        $this->pair = $this->argument('pair');

        $parse = explode('_', $this->pair);
        $this->base = $parse[0];
        $this->quote = $parse[1];

        $this->info = $this->iddx->info();
        $this->trades = $this->iddx->tradeHistory([
            'pair' => $this->pair,
            'since' => $this->startedAt
        ]);

        $this->tick = $this->_getTicker();
        $this->pivot = $this->_getPivot($this->settings['pivot_type']);

        $this->_updateIndicator();
        $signal = $this->_getSignal();

        if (
            $signal['signal'] === 'buy' 
            && $this->rsi <= $this->settings['rsi_max_number'] 
            && $this->settings['rsi_max_number'] > 0
            && $this->sma >= $signal['price']
        ) {
            $orderData = $this->iddx->createOrder([
                'type' => 'buy',
                'pair' => $this->pair,
                'price' => $signal['price'],
                $this->quote => $signal['amount']
            ]);

            if (isset($orderData['order_id'])) {
                if ($this->settings['telegram_chat_id'] != 0) {
                    $isSendMessage = true;
                    $messageText = $this->_summaryText();
                    $messages[] = [
                        'header' => ['info', 'desc'],
                        'data' => [
                            ['Event', 'BUY'],
                            ['Price', $signal['price']],
                            ['Amount', $signal['amount'] . ' ' . strtoupper($this->quote)],
                            ['P/L', $messageText]
                        ]
                    ];
                }
            }
        } else if ($signal['signal'] === 'sell') {
            $minPrice = $this->_getMinimumPrice();
            if ($minPrice < $signal['price']) {
                $orderData = $this->iddx->createOrder([
                    'type' => 'sell',
                    'pair' => $this->pair,
                    'price' => $signal['price'],
                    $this->base => $signal['amount']
                ]);

                if (isset($orderData['order_id'])) {
                    if ($this->settings['telegram_chat_id'] != 0) {
                        $isSendMessage = true;
                        $messages[] = [
                            'header' => ['info', 'desc'],
                            'data' => [
                                ['Event', 'SELL'],
                                ['Price', $signal['price']],
                                ['Amount', $signal['amount'] . ' ' . strtoupper($this->base)]
                            ]
                        ];    
                    }
                }
            }
        } else if ($signal['signal'] === 'cancel') {
            $orderData = $this->iddx->cancelOrder([
                'type' => $signal['type'],
                'pair' => $this->pair,
                'order_id' => $signal['order_id']
            ]);
        }

        if ($isSendMessage && $this->settings['telegram_chat_id'] > 0) {
            $this->line('* ' .strtoupper($this->base) . '/' . $this->quote . ' * _' . date('d-m-Y H:i') . '_');
            $this->line('```');
            foreach ($messages as $message) {
                $this->table($message['header'], $message['data']);
            }
            $this->line('```');

            $message = $this->_filter($this->output->fetch());

            $telegram = new Api(config('telegram.bots.the_pithik_bot.token'));
            $response = $telegram->sendMessage([
                'chat_id' => $this->settings['telegram_chat_id'],
                'parse_mode' => 'MarkdownV2',
                'text' => $message
            ]);
        }
    }

    private function _getMinimumPrice() {
        $minPrice = 0;
        foreach ($this->trades as $trade) {
            if ($trade['type'] === 'buy') {
                $minPrice = $trade['price'];
                break;
            }
        }

        return $minPrice;
    }

    private function _getSignal() {
        $openOrders = $this->iddx->openOrders([
            'pair' => $this->pair
        ]);

        $baseBalance = isset($this->info['balance']) ? $this->info['balance'][$this->base] : -1;
        $quoteBalance = isset($this->info['balance']) ? $this->info['balance'][$this->quote] : -1;

        $signal = [ 'signal' => 'wait' ];

        if (!$this->tick || !$this->pivot || !isset($openOrders['orders']) || $baseBalance == -1) {
            $signal['signal'] = 'wait';
        } else if (count($openOrders['orders']) === 0) {
            $signal['signal'] = 'wait';
            if ($baseBalance > $this->settings['trade_min_traded_currency']) {
                $signal['signal'] = 'sell';
                $signal['amount'] = $baseBalance;
                if ($this->pivot['r01'] > $this->tick) {
                    $signal['price'] = $this->pivot['r01'];
                } else if ($this->pivot['r02'] > $this->tick) {
                    $signal['price'] = $this->pivot['r02'];
                }
            } else if ($quoteBalance > $this->settings['trade_min_base_currency']) {
                $signal['signal'] = 'buy';
                $signal['amount'] = $quoteBalance > $this->settings['max_amount'] ? $this->settings['max_amount'] : $quoteBalance;
                if ($this->pivot['s01'] < $this->tick) {
                    $signal['price'] = $this->pivot['s01'];
                } else if ($this->pivot['s02'] < $this->tick) {
                    $signal['price'] = $this->pivot['s02'];
                }
            }
        } else if ($openOrders['orders'][0]['type'] === 'buy') {
            $signal['signal'] = 'cancel';
            if ($this->pivot['s01'] == $openOrders['orders'][0]['price']) {
                $signal['signal'] = 'wait';
            } else if ($this->pivot['s02'] == $openOrders['orders'][0]['price']) {
                $signal['signal'] = 'wait';
            } else {
                $signal['order_id'] = $openOrders['orders'][0]['order_id'];
            }
        } 

        return $signal;
    }

    private function _summaryText() {
        $sellTotal = 0;
        $buyTotal = 0;
        foreach ($this->trades as $trade) {
            if ($trade['type'] === 'sell') {
                $sellTotal = $sellTotal + round($trade[$this->base] * $trade['price']);
            } else if ($trade['type'] === 'buy') {
                $buyTotal = $buyTotal + $trade[$this->quote];
            }
        }

        return str_pad(number_format($sellTotal - $buyTotal, 0, ',', '.'), 12, " ", STR_PAD_LEFT);
    }

    private function _getPivot($type) {
        $pair = str_replace('_', '', $this->pair);
        $data = $this->iddx->history([
            'symbol' => strtoupper($pair),
            'resolution' => $this->settings['pivot_resolution'],
            'from' => strtotime($this->resolutionPivot[$this->settings['pivot_resolution']]),
            'to' => strtotime('now')
        ]);

        if (empty($data)) {
            return false;
        }

        $h = $data['h'][0];
        $l = $data['l'][0];
        $c = $data['c'][0];

        switch ($type) {
            case 'default':
                $pp = round(($h + $l + $c) / 3);
                $r02 = $pp + ($h - $l);
                $r01 = (2 * $pp) - $l;
                $s01 = (2 * $pp) - $h;
                $s02 = $pp - ($h - $l);
                break;
            case 'woodie':
                $pp = round(($h + $l + (2 * $c)) / 4);
                $r02 = $pp + ($h - $l);
                $r01 = (2 * $pp) - $l;
                $s01 = (2 * $pp) - $h;
                $s02 = $pp - ($h - $l);
                break;
            case 'camarilla':
                $pp = round(($h + $l + $c) / 3);
                $r02 = $c + ceil(($h - $l) * 1.1666);
                $r01 = $c + round(($h - $l) * 1.0833);
                $s01 = $c - round(($h - $l) * 1.0833);
                $s02 = $c - ceil(($h - $l) * 1.1666);
                break;
            case 'fibonacci':
                $pp = round(($h + $l + $c) / 3);
                $r02 = $pp + round(($h - $l) * 0.618);
                $r01 = $pp + round(($h - $l) * 0.382);
                $s01 = $pp - round(($h - $l) * 0.382);
                $s02 = $pp - round(($h - $l) * 0.618);
                break;
        }

        return [
            'r02' => $r02, 'r01' => $r01, 'pp' => $pp, 's01' => $s01, 's02' => $s02
        ];
    }

    private function _updateIndicator() {
        $pair = str_replace('_', '', $this->pair);
        $data = $this->iddx->history([
            'symbol' => strtoupper($pair),
            'resolution' => $this->settings['rsi_resolution'],
            'from' => strtotime($this->resolutionRSI[$this->settings['rsi_resolution']]),
            'to' => strtotime('now')
        ]);

        $points = trader_rsi($data['c'], 14);
        if ($points) {
            $this->rsi = round(array_pop($points));
        }

        $points = trader_sma($data['c'], 21);
        if ($points) {
            $this->sma = array_pop($points);
        }
    }

    private function _getTicker() {
        $pair = str_replace('_', '', $this->pair);
        $tick = $this->iddx->ticker($pair);
        if (!$tick) {
            return false;
        }

        return intval($tick['last']);
    }

    private function _filter($str) {
        return str_replace('|', '\|', 
            str_replace('-', '\-', 
            str_replace('+', '\+',
            str_replace('.', '\.',
            str_replace('(', '\(',
            str_replace(')', '\)', $str
        ))))));
    }
}
