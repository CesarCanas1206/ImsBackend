<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class MigrationController extends APIController
{
    public function setup($siteName = null)
    {
        date_default_timezone_set("Australia/Brisbane");
        $config = Config::get('database.connections.mysql');
        $config['username'] = env('DB_MASTER_USERNAME');
        $config['password'] = env('DB_MASTER_PASSWORD');

        if ($siteName) {
            $config['database'] = str_replace('{site}', $siteName, env('DB_DATABASE'));
        }

        Config::set('database.connections.mysql', $config);
    }

    public function __construct()
    {
        $this->setup();
    }

    public function run()
    {
        Artisan::call('migrate', ['--force' => true]);
        return response()->json(['data' => 'Migration complete']);
    }

    public function runMany()
    {
        $sites = [
            'appdev',
            'bookings',
            'stonningtonnew',
            'stonnington2',
            'warrnamboolnew',
            'monashnew',
            'gleneiranew',
            'imstest',
            'brimbanknew',
            'brimbankreserves',
        ];

        foreach ($sites as $site) {
            $this->setup($site);
            Artisan::call('migrate', ['--force' => true]);
        }
        return response()->json(['data' => 'Migration complete']);
    }
}
