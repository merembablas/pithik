<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\IndodaxService;
use App\Services\BinanceService;
use App\Services\CoinMarketCapService;
use App\Services\FixerService;

class Ticker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:ticker {timeframe=1m : Timeframe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get latest price';

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
    public function handle(IndodaxService $iddx, BinanceService $bnc, CoinMarketCapService $cmc, FixerService $fixer)
    {
        $iddxData = $iddx->tickerAll();
        $bncData = $bnc->tickerAll();
        $cmcData = $cmc->tickerAll();
        
        $usdtidr = isset($iddxData['usdt_idr']) ? $iddxData['usdt_idr']['last'] : 0;
        $usdidr = $fixer->usdtoidr();

        $data = [];
        foreach ($iddxData as $pair => $item) {
            $split = explode('_', $pair);
            $coin = $split[0];
            $pairing = $split[1];
            $vol = 0;
            $price = 0;

            if ($pairing === 'idr') {
                $vol = $item['vol_idr'];
                $price = $item['last'];
            } else if ($pairing === 'usdt') {
                $vol = round($item['vol_usdt'] * $usdtidr);
                $price = round($item['last'] * $usdtidr);
            }

            $bncPrice = isset($bncData[$coin . 'usdt']) ? round($bncData[$coin . 'usdt'] * $usdtidr) : 0;
            $cmcPrice = isset($cmcData[$coin]) ? round($cmcData[$coin] * $usdidr) : 0;

            $data[] = [
                'pair' => $coin . $pairing,
                'timeframe' => $this->argument('timeframe'),
                'price_iddx' => $price,
                'price_bnc' => $bncPrice,
                'price_cmc' => $cmcPrice,
                'vol_iddx' => $vol,
                'created_at' => date('Y-m-d H:i:s', (int) $item['server_time']),
                'updated_at' => Carbon::now()
            ];
        }

        DB::table('tickers')->insert($data);
    }
}
