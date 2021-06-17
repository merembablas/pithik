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
    protected $iddx, $tick, $pair, $base, $quote, $startedAt, $settings, $corrOrders, $info, $trades;

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
        $bots = DB::table('bots')
            ->where('type', 'grid')
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

        $this->trades = $this->iddx->tradeHistory([
            'pair' => $this->pair,
            'since' => $this->startedAt
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

        foreach (range($this->settings['lower_price'], $this->settings['upper_price'], $this->settings['pricescale']) as $price) {
            $nextPrice = $price + $this->settings['pricescale'];
            $nextOrder = isset($orders[$nextPrice]) ? $orders[$nextPrice] : 'none'; 
            if (!isset($orders[$price]) && $price < $lastPrice && $nextOrder != 'sell') {
                $info = $this->iddx->info();
                $quoteBalance = isset($info['balance']) ? $info['balance'][$this->quote] : 0;

                if ($quoteBalance >= $this->settings['max_amount']) {
                    $orderData = $this->iddx->createOrder([
                        'type' => 'buy',
                        'pair' => $this->pair,
                        'price' => $price,
                        $this->quote => $this->settings['max_amount']
                    ]);

                    if (isset($orderData['order_id'])) {
                        $isSendMessage = true;
                        $messages[] = [
                            'header' => ['info', 'desc'],
                            'data' => [
                                ['Event', 'BUY'],
                                ['Price', $price],
                                ['Amount', $this->settings['max_amount'] . ' ' . strtoupper($this->quote)]
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

        $lastTradeId = isset($this->settings['last_trade_id']) ? $this->settings['last_trade_id'] : 0;
        if (isset($this->trades[0]) && $lastTradeId != $this->trades[0]['trade_id'] && $this->trades[0]['type'] === 'sell') {
            $isSendMessage = true;
            $PLMessage = $this->_summaryText();
            $info = $this->iddx->info();
            $quoteBalance = isset($info['balance']) ? $info['balance'][$this->quote] : '-';
            $messages[] = [
                'header' => ['info', 'desc'],
                'data' => [
                    ['IDR', str_pad(number_format($quoteBalance, 0, ',', '.'), 12, " ", STR_PAD_LEFT)],
                    ['P/L', str_pad($PLMessage, 12, " ", STR_PAD_LEFT)]
                ]
            ];

            $this->settings['last_trade_id'] = $this->trades[0]['trade_id'];
            DB::table('bots')->where('id', $bots[0]->id)->update([
                'settings' => json_encode($this->settings),
                'updated_at' => date('Y-m-d H:i:s')
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

    private function _getTicker() {
        $pair = str_replace('_', '', $this->pair);
        $tick = $this->iddx->ticker($pair);
        if (!$tick) {
            return false;
        }

        return intval($tick['last']);
    }

    private function _summaryText() {
        $profit = 0;
        foreach ($this->trades as $trade) {
            if ($trade['type'] === 'sell') {
                $amount = round($trade[$this->base] * $trade['price']);
                $amount = $amount > $this->settings['max_amount'] ? ($amount  - $this->settings['max_amount']) : 0; 
                $profit = $profit + $amount;
            }
        }

        return number_format($profit, 0, ',', '.');
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
