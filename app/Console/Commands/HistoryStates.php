<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class HistoryStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Reservations:ChangeToHistory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reservation states change to history automatic daily';

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
       
        DB::table("reservations")->where('status','=',1)->update(['status'=>3]);
    }
}
