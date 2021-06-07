<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\IndicatorService;
use App\Services\IndodaxService;

class IndicatorRSI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indicator:rsi {periode=14 : Periode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'RSI Indicator';

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
    public function handle(IndicatorService $indicator, IndodaxService $iddx)
    {
        $data = $indicator->rsi($this->argument('periode'), $iddx);

        $this->line('RSI ' . $this->argument('periode') . ' ' . date('d-m-Y H:i'));
        $this->table(['IDDX', '15m', '1h', '4h'], $data['iddx']);
        $this->table(['BNC', '15m', '1h', '4h'], $data['bnc']);
    }
}
