<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\IndodaxService;

class Depth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:depth {pair=btcidr}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Orderbook';

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
        $data = $iddx->depth($this->argument('pair'));
        $lastTickers = DB::table('tickers')->where('pair', $this->argument('pair'))->orderBy('created_at', 'desc')->limit(1)->get();
        $lastPrice = $lastTickers[0]->price_iddx;

        $bids = [];
        foreach ($data['buy'] as $item) {
            $bids[$item[0]] = round($item[0] * $item[1]);
        }

        $asks = [];
        foreach ($data['sell'] as $item) {
            $asks[$item[0]] = round($item[0] * $item[1]);
        }

        $orderBooks = $asks + $bids;
        arsort($orderBooks);
        $bestOrderBooks = array_slice($orderBooks, 0, 10, true);
        $bestOrderBooks[$lastPrice] = 'Current';
        krsort($bestOrderBooks, SORT_NUMERIC);

        $oBData = [];
        $startPrice = 0;
        foreach ($bestOrderBooks as $k => $v) {
            if ($startPrice == 0) {
                $startPrice = $k;
            } else {
                $totalBetween = 0;
                foreach ($orderBooks as $obP => $obV) {
                    if ($obP < $startPrice && $obP > $k) {
                        $totalBetween += $obV;
                    }
                }

                $startPrice = $k;
                $oBData[] = [ '-', $totalBetween, ''];
            }

            if (!is_numeric($v)) {
                $oBData[] = [ $k, $v, '0.00' ];
            } else {
                $sign = $k > $lastPrice ? '+' : '-';
                $percent = number_format((abs($k - $lastPrice) / $lastPrice) * 100, 2, '.', '.');
                $oBData[] = [ $k, $v, $sign . $percent ];
            }                
        }

        $this->line('```');
        $this->table(['Market', 'Amount', 'Percent'], $oBData);
        $this->line('```');
    }
}
