<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Google\Service\Drive;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // فرض HTTPS على كل الروابط المولّدة (بس بالإنتاج، مش أثناء التطوير المحلي)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        Storage::extend('google', function ($app, $config) {
        $client = new \Google\Client();
        $client->setClientId($config['clientId']);
        $client->setClientSecret($config['clientSecret']);
        $client->refreshToken($config['refreshToken']);

        $service = new Drive($client);
       // $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? null);
       $folderId = $config['folderId'] ?? $config['folder'] ?? 'root';
$adapter = new GoogleDriveAdapter($service, $folderId);
        return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
    });
    }
}