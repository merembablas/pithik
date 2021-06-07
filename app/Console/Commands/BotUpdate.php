<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\IndodaxService;

class BotUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:update {uuid} {settings*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bot Update';

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
        $bots = DB::table('bots')
            ->where('uuid', $this->argument('uuid'))
            ->get();

        if (count($bots) === 0) {
            return 0;
        }

        $currentSettings = json_decode($bots[0]->settings, true);
        $settings = $this->argument('settings');
        foreach ($settings as $setting) {
            $split = explode(':', $setting);
            if (isset($currentSettings[$split[0]]) && isset($split[1])) {
                $currentSettings[$split[0]] = $split[1];
            }
        }

        $info = [];
        foreach ($currentSettings as $label => $value) {
            $info[] = [$label, $value];
        }

        $this->table(
            ['label', 'value'],
            $info
        );

        if ($this->confirm('Do you wish to continue?')) {
            DB::table('bots')->where('uuid', $this->argument('uuid'))->update([
                'settings' => json_encode($currentSettings),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->info('Bot Updated!');
        }
    }
}
