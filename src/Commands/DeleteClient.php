<?php

namespace Vinothst94\LaravelSingleSignOn\Commands;

use Illuminate\Console\Command;

class DeleteClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:client:delete {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete client with specified name.';

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
        $clientClass = app(config('laravel-sso.clientsModel'));
        $client = $clientClass::where('name', $this->argument('name'))->firstOrFail();
        $client->delete();

        $this->info('Client with name `' . $this->argument('name') . '` successfully deleted.');
    }
}
