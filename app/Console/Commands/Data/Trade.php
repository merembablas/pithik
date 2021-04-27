<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\IndodaxService;

class Trade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:trade {timeframe=5m : Timeframe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get latest transaction';

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
        $currentHour = date('Y-m-d H:00:00');
        $theTime = strtotime($currentHour);
        $partition = [ $theTime ];
        foreach (range(1, 11) as $number) {
            $partition[] = $theTime + ($number * 300) - 1;
            $partition[] = $theTime + ($number * 300);
        }

        $partition[] = $theTime + (12 * 300) - 1;
        $partition = array_chunk($partition, 2);

        $pairs = $this->_getQueue($iddx->pairs());
        $result = $iddx->trade($pairs);
        foreach ($result as $pair => $trades) {
            $options = DB::table('options')->where('key', 'last_tid_' . $pair)->limit(1)->get();
            $lastTid = isset($options[0]) ? (int) $options[0]->value : 0;
            $buyTotal = [];
            $buyPrice = [];
            $sellTotal = [];
            $sellPrice = [];
            foreach ($trades as $trade) {
                $amount = round($trade['price'] * $trade['amount']);
                if ($lastTid < (int) $trade['tid']) {
                    foreach ($partition as $part) {
                        if ($part[0] <= (int) $trade['date'] && $part[1] >= (int) $trade['date']) {
                            if ($trade['type'] === 'buy') {
                                $buyTotal[$part[1]] = isset($buyTotal[$part[1]]) ? $buyTotal[$part[1]] + $amount : $amount;
                                $buyPrice[$part[1]] = isset($buyPrice[$part[1]]) ? $buyPrice[$part[1]] : $trade['price'];
                            } else {
                                $sellTotal[$part[1]] = isset($sellTotal[$part[1]]) ? $sellTotal[$part[1]] + $amount : $amount;
                                $sellPrice[$part[1]] = isset($sellPrice[$part[1]]) ? $sellPrice[$part[1]] : $trade['price'];
                            }
                        }
                    }
                }
            }

            foreach ($buyTotal as $k => $v) {
                $trade =  DB::table('trades')
                    ->where('pair', $pair)
                    ->where('type', 'buy')
                    ->whereDate('created_at', date('Y-m-d H:i:s', $k))
                    ->get();

                if (count($trade) > 0) {
                    DB::table('trades')->where('id', $trade[0]->id)->update([
                        'price' => isset($buyPrice[$k]) ? $buyPrice[$k] : 0,
                        'amount' => $trade[0]->amount + $v
                    ]);
                } else {
                    DB::table('trades')->insert([
                        'pair' => $pair,
                        'timeframe' => $this->argument('timeframe'),
                        'price' => isset($buyPrice[$k]) ? $buyPrice[$k] : 0,
                        'amount' => $v,
                        'type' => 'buy',
                        'created_at' => date('Y-m-d H:i:s', $k)
                    ]);
                }
            }

            foreach ($sellTotal as $k => $v) {
                $trade =  DB::table('trades')
                    ->where('pair', $pair)
                    ->where('type', 'sell')
                    ->whereDate('created_at', date('Y-m-d H:i:s', $k))
                    ->get();

                if (count($trade) > 0) {
                    DB::table('trades')->where('id', $trade[0]->id)->update([
                        'price' => isset($sellPrice[$k]) ? $sellPrice[$k] : 0,
                        'amount' => $trade[0]->amount + $v
                    ]);
                } else {
                    DB::table('trades')->insert([
                        'pair' => $pair,
                        'timeframe' => $this->argument('timeframe'),
                        'price' => isset($sellPrice[$k]) ? $sellPrice[$k] : 0,
                        'amount' => $v,
                        'type' => 'sell',
                        'created_at' => date('Y-m-d H:i:s', $k)
                    ]);
                }
            }

            if (count($options) > 0) {
                DB::table('options')->where('key', 'last_tid_' . $pair)->update([
                    'value' => $trades[0]['tid'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                DB::table('options')->insert([
                    'key' => 'last_tid_' . $pair,
                    'value' => $trades[0]['tid'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    private function _getQueue($pairs) {
        $chunks = array_chunk($pairs, 30);

        $queue = Cache::get('trade_pairs_queue');
        if (!$queue) {
            $queue = 1;
            Cache::put('trade_pairs_queue', $queue);
        } else {
            $queue = $queue >= 5 ? 1 : $queue + 1;
            Cache::put('trade_pairs_queue', $queue);
        }

        return $chunks[$queue - 1];
    }
}
