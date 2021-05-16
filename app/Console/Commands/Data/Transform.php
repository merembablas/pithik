<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\IndodaxService;

class Transform extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:transform {timeframe=1d : Timeframe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transform tickers and trades to daily timeframe';

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
        $todayStart = strtotime('06:00:00');
        $yesterdayStart = strtotime('-1 day', $todayStart);
        $yesterdayEnd = $todayStart - 1;

        $pairs = $iddx->pairs();
        foreach ($pairs as $pair) {
            // DAILY TRADE
            $trades = DB::table('trades')
                ->where('pair', $pair)
                ->where('timeframe', '15m')
                ->whereDate('created_at', '>=', date('Y-m-d H:i:s', $yesterdayStart))
                ->whereDate('created_at', '<=', date('Y-m-d H:i:s', $yesterdayEnd))
                ->orderBy('created_at', 'asc')->get();

            $totalBuy = 0;
            $priceBuy = 0;
            $totalSell = 0;
            $priceSell = 0;
            foreach ($trades as $trade) {
                if ($trade->type === 'buy') {
                    $totalBuy = $totalBuy + $trade->amount;
                    $priceBuy = $trade->price;
                } else {
                    $totalSell = $totalSell + $trade->amount;
                    $priceSell = $trade->price;
                }
            }

            DB::table('trades')->insert([
                'pair' => $pair,
                'timeframe' => '1d',
                'price' => $priceBuy,
                'amount' => $totalBuy,
                'type' => 'buy',
                'created_at' => date('Y-m-d H:i:s', $yesterdayEnd)
            ]);

            DB::table('trades')->insert([
                'pair' => $pair,
                'timeframe' => '1d',
                'price' => $priceSell,
                'amount' => $totalSell,
                'type' => 'sell',
                'created_at' => date('Y-m-d H:i:s', $yesterdayEnd)
            ]);


            // DAILY TICKER
            $tickers = DB::table('tickers')
                ->where('pair', $pair)
                ->where('timeframe', '1m')
                ->whereDate('created_at', '>=', date('Y-m-d H:i:s', $yesterdayStart))
                ->whereDate('created_at', '<=', date('Y-m-d H:i:s', $yesterdayEnd))
                ->orderBy('created_at', 'desc')->get();
            
            if (count($tickers) > 0) {
                DB::table('tickers')->insert([
                    'pair' => $pair,
                    'timeframe' => '1d',
                    'price_iddx' => $tickers[0]->price_iddx,
                    'price_cmc' => $tickers[0]->price_cmc,
                    'price_bnc' => $tickers[0]->price_bnc,
                    'vol_iddx' => $tickers[0]->vol_iddx,
                    'created_at' => date('Y-m-d H:i:s', $yesterdayEnd),
                    'updated_at' => date('Y-m-d H:i:s', $yesterdayEnd)
                ]);
            }
        }
    }
}
