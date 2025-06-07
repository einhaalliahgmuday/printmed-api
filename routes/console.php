<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('make:service {name}', function ($name) {
    $path = app_path("Services/{$name}.php");

    if (file_exists($path)) {
        $this->error("Service {$name} already exists!");
        return;
    }

    if (!is_dir(app_path('Services'))) {
        mkdir(app_path('Services'), 0755, true);
    }

    $template = "<?php

namespace App\Services;

class {$name}
{
    
}";

    file_put_contents($path, $template);

    $this->info("Service {$name} created successfully.");
})->describe('Create a new service class');
