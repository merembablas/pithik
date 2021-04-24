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
    protected $signature = 'data:purge {timeframe=1m : Timeframe}';

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
            ->where('timeframe', $this->argument('timeframe'))
            ->whereDate('created_at', '<=', date('Y-m-d H:i:s', $purgeMaxDate))
            ->delete();

        DB::table('trades')
            ->where('timeframe', $this->argument('timeframe'))
            ->whereDate('created_at', '<=', date('Y-m-d H:i:s', $purgeMaxDate))
            ->delete();
    }
}
