<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BotStop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:stop {uuid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bot Stop';

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
        $bots = DB::table('bots')
            ->where('uuid', $this->argument('uuid'))
            ->get();

        if (count($bots) === 0) {
            return 0;
        }

        if ($this->confirm('Do you wish to continue?')) {
            DB::table('bots')->where('uuid', $this->argument('uuid'))->update([
                'status' => 'inactive',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
