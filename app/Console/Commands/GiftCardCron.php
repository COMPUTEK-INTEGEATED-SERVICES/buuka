<?php

namespace App\Console\Commands;

use App\CronJobs\GiftCardNotificationAndSMSSenderCronJob;
use Illuminate\Console\Command;

class GiftCardCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:giftcard';

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
        return (new GiftCardNotificationAndSMSSenderCronJob())->call();
    }
}
