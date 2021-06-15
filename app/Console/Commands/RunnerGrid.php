<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\IndodaxService;
use Telegram\Bot\Api;
use Symfony\Component\Console\Output\BufferedOutput;

class RunnerGrid extends Command
{
    protected $iddx, $tick, $pivot, $rsi, $sma, $pair, $base, $quote, $settings, $info, $trades;
    protected $resolutionRSI = [ 15 => '-90 hours', 60 => '-15 days', 240 => '-60 days', 'D' => '-360 days' ];
    protected $resolutionPivot = [ 15 => '-30 minutes', 60 => '-2 hours', 240 => '-8 hours', 'D' => '-2 days' ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:run {pair}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grid Runner';

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
        $this->iddx = $iddx;
        $this->pair = $this->argument('pair');

        $maxPrice = 500000;
        $upperPrice = 55;
        $lowerPrice = 47;

        $output = new BufferedOutput;
        $this->output = $output;
        $isSendMessage = false;
        $telegramChatId = 29160291;
        $messages = [];

        $parse = explode('_', $this->pair);
        $this->base = $parse[0];
        $this->quote = $parse[1];

        $this->trades = $this->iddx->tradeHistory([
            'pair' => $this->pair,
            'since' => strtotime('2021-06-08')
        ]);
 
        $lastPrice = $this->_getTicker();
        if (!$lastPrice) {
            return 0;
        }

        $data = $iddx->openOrders([
            'pair' => $this->pair,
        ]);

        if (!isset($data['orders'])) {
            return 0;
        }

        $orders = [];
        foreach ($data['orders'] as $order) {
            $orders[$order['price']] = $order['type'];
        }

        foreach (range($lowerPrice, $upperPrice) as $price) {
            if (!isset($orders[$price]) && $price < $lastPrice) {
                $info = $this->iddx->info();
                $quoteBalance = isset($info['balance']) ? $info['balance'][$this->quote] : 0;

                if ($quoteBalance >= $maxPrice) {
                    $orderData = $this->iddx->createOrder([
                        'type' => 'buy',
                        'pair' => $this->pair,
                        'price' => $price,
                        $this->quote => $maxPrice
                    ]);

                    if (isset($orderData['order_id'])) {
                        $isSendMessage = true;
                        $messageText = $this->_summaryText();
                        $messages[] = [
                            'header' => ['info', 'desc'],
                            'data' => [
                                ['Event', 'BUY'],
                                ['Price', $price],
                                ['Amount', $maxPrice . ' ' . strtoupper($this->quote)],
                                ['P/L', $messageText]
                            ]
                        ];
                    }
                }
            } else if (!isset($orders[$price]) && $price >= $lastPrice) {
                $info = $this->iddx->info();
                $baseBalance = isset($info['balance']) ? $info['balance'][$this->base] : 0;
                
                if ($baseBalance > 0) {
                    if (!isset($this->trades[0]) || $this->trades[0]['type'] === 'sell') {
                        continue;
                    }

                    if ($this->trades[0]['price'] < $price) {
                        $orderData = $this->iddx->createOrder([
                            'type' => 'sell',
                            'pair' => $this->pair,
                            'price' => $price,
                            $this->base => $baseBalance
                        ]);

                        if (isset($orderData['order_id'])) {
                            $isSendMessage = true;
                            $messages[] = [
                                'header' => ['info', 'desc'],
                                'data' => [
                                    ['Event', 'SELL'],
                                    ['Price', $price],
                                    ['Amount', $baseBalance . ' ' . strtoupper($this->base)],
                                    ['Total', number_format(round($baseBalance * $price), 0, ',', '.') . ' ' . strtoupper($this->quote)]
                                ]
                            ];
                        }
                    }
                }

            }
        }

        if ($isSendMessage) {
            $this->line('* ' .strtoupper($this->base) . '/' . $this->quote . ' * _' . date('d-m-Y H:i') . '_');
            $this->line('```');
            foreach ($messages as $message) {
                $this->table($message['header'], $message['data']);
            }
            $this->line('```');

            $message = $this->_filter($this->output->fetch());

            $telegram = new Api(config('telegram.bots.the_pithik_bot.token'));
            $response = $telegram->sendMessage([
                'chat_id' => $telegramChatId,
                'parse_mode' => 'MarkdownV2',
                'text' => $message
            ]);
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
