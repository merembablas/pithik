<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\IndodaxService;

class BotStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:start {type} {pair} {max_amount} {settings*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bot Start';

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
        $type = $this->argument('type');
        $pair = $this->argument('pair');
        $maxAmount = $this->argument('max_amount');
        $settings = $this->argument('settings');
        $brokerData = [];

        $pairData = $iddx->pairs();
        foreach ($pairData as $item) {
            if ($item['ticker_id'] === $pair) {
                $brokerData = $item;
            }
        }

        if (empty($brokerData)) {
            return 0;
        }

        $defaultSettings = [
            'trade_min_base_currency' => $brokerData['trade_min_base_currency'],
            'trade_min_traded_currency' => $brokerData['trade_min_traded_currency'],
            'trade_fee_percent' => $brokerData['trade_fee_percent'],
            'pricescale' => $brokerData['pricescale'],
            'max_amount' => $maxAmount,
            'max_orders' => 1,
            'pivot_type' => 'woodie',
            'pivot_resolution' => 240,
            'rsi_resolution' => 'D',
            'rsi_max_number' => 50,
            'telegram_chat_id' => 0
        ];

        foreach ($settings as $setting) {
            $split = explode(':', $setting);
            if (isset($defaultSettings[$split[0]]) && isset($split[1])) {
                $defaultSettings[$split[0]] = $split[1];
            }
        }

        $info = [
            ['type', $type],
            ['pair', $pair]
        ];
    
        foreach ($defaultSettings as $label => $value) {
            $info[] = [$label, $value];
        }

        $this->table(
            ['label', 'value'],
            $info
        );

        if ($this->confirm('Do you wish to continue?')) {
            DB::table('bots')->insert([
                'uuid' => (string) Str::uuid(),
                'type' => $type,
                'pair' => $pair,
                'corresponding_orders' => json_encode([]),
                'settings' => json_encode($defaultSettings),
                'status' => 'active',
                'started_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->info('Bot Created!');
        }
    }
}
