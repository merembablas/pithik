<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Purge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:purge {timeframe=15m : Timeframe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge trade, ticker, and depth data';

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
        $todayStart = strtotime('08:00:00');
        $purgeMaxDate = strtotime('-8 day', $todayStart);

        DB::table('tickers')
            ->where('timeframe', '1m')
            ->whereDate('created_at', '<=', date('Y-m-d H:i:s', $purgeMaxDate))
            ->delete();

        DB::table('trades')
            ->where('timeframe', '15m')
            ->whereDate('created_at', '<=', date('Y-m-d H:i:s', $purgeMaxDate))
            ->delete();
    }
}
