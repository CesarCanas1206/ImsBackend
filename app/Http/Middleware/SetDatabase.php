<?php

namespace App\Http\Middleware;

use App\Models\Config as Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SetDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $site_name = $request->header('x-site-name') ?? $request->get('site-name') ?? 'bookings';
        $config = Config::get('database.connections.mysql');
        $config['database'] = str_replace('{site}', $site_name, env('DB_DATABASE'));
        $site_name = str_replace('copy', '', $site_name);
        if (strlen($site_name) > 12) {
            $site_name = substr($site_name, 0, 12);
        }
        $config['username'] = str_replace('{site}', $site_name, env('DB_USERNAME'));
        $config['password'] = str_replace('{site}', $site_name, env('DB_PASSWORD'));
        Config::set('database.connections.mysql', $config);
        if ($request->isMethod('post')) {
            $timezone = Settings::where('code', 'timezone')->pluck('value')->first();
            if (!empty($timezone)) {
                Config::set('app.timezone', $timezone);
            }
        }

        return $next($request);
    }
}
