<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\CoinMarketCalService;

class News extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get News';

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
    public function handle(CoinMarketCalService $cmc)
    {
        $news = $cmc->news();
        
        foreach ($news as $item) {
            $resultNews = DB::table('news')
                ->where('coin', $item['coin'])
                ->whereDate('date_event', '=', $item['date_event'])
                ->where('title', $item['title'])
                ->get();

            if (!isset($resultNews[0])) {
                DB::table('news')->insert($item);
            }
        }
    }
}
