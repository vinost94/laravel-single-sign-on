<?php

namespace Vinothst94\LaravelSingleSignOn\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:client:create {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating new SSO client.';

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
        $client = new $clientClass;

        $client->name = $this->argument('name');
        $client->secret = Str::random(40);

        $client->save();

        $this->info('Client with name `' . $this->argument('name') . '` successfully created.');
        $this->info('Secret: ' . $client->secret);
    }
}
