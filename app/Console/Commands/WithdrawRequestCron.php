<?php

namespace App\Console\Commands;

use App\CronJobs\WithdrawalRequestCronJob;
use Illuminate\Console\Command;

class WithdrawRequestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:withdrawal_request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        return (new WithdrawalRequestCronJob())->call();
    }
}
