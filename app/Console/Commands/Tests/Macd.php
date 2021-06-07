<?php

namespace App\Console\Commands\Tests;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\IndodaxService;

class Macd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:macd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'MACD Test';

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
        ini_set('trader.real_precision', '6');
        $pair = 'btcidr';
        $tf = 'D';
        $startTime = '-360 days';
        $start = strtotime($startTime);
        $end = strtotime('now');

        $response = Http::get('https://indodax.com/tradingview/history?symbol='.strtoupper($pair).'&resolution='.$tf.'&from='.$start.'&to='.$end);
        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['s']) && $data['s'] === 'ok') {
                $ema12 = trader_ema($data['c'], 12);
                $ema26 = trader_ema($data['c'], 26);
                $ema12 = array_slice($ema12, 14);
                $ema26 = array_values($ema26);

                $macdLine = array();
                foreach ($ema12 as $key => $value) {
                    $macdLine[$key] = $ema12[$key] - $ema26[$key];
                }

                $signalLine = trader_sma($macdLine, 9);

                $mcLast = array_pop($macdLine);
                $mcPrev = array_pop($macdLine);
                $slLast = array_pop($signalLine);
                $slPrev = array_pop($signalLine);

                echo $mcLast . "\n";
                echo $slLast . "\n";

                // KVO
                $dmData = [];
                $cmData = [];
                $trendData = [];
                $hclData = [];
                $vfData = [];
                foreach ($data['c'] as $index => $v) {
                    $prevIndex = $index === 0 ? 0 : $index - 1;
                    $prevHcl = isset($hclData[$prevIndex]) ? $hclData[$prevIndex] : 0; 
                    $hclData[] = ($data['h'][$index] + $data['c'][$index] + $data['l'][$index]) / 3;
                    $trendData[] = $hclData[$index] > $prevHcl ? 1 : -1;
                    $vfData[] = $trendData[$index] * $data['v'][$index];
                }

                $ema34 = trader_ema($vfData, 34);
                $ema55 = trader_ema($vfData, 55);
                $ema34 = array_slice($ema34, 21);
                $ema55 = array_values($ema55);
  
                $kvoLine = array();
                foreach ($ema34 as $key => $value) {
                    $kvoLine[$key] = $ema34[$key] - $ema55[$key];
                }
                $triggerLine = trader_ema($kvoLine, 13);
                $kvoLast = array_pop($kvoLine);
                $triggerLast = array_pop($triggerLine);
                echo $kvoLast . "\n";
                echo $triggerLast . "\n";
            }
        }
    }
}
