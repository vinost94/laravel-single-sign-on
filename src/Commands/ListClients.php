<?php

namespace Vinothst94\LaravelSingleSignOn\Commands;

use Illuminate\Console\Command;

class ListClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:client:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all created clients.';

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
     * @return mixed
     */
    public function handle()
    {
        $headers = ['ID', 'Name', 'Secret'];

        $clientClass = app(config('laravel-sso.clientsModel'));
        $clients = $clientClass::all(['id', 'name', 'secret'])->toArray();

        $this->table($headers, $clients);
    }
}
