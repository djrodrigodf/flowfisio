<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
        Blade::directive('display', function ($expression) {
            [$label, $value] = explode(',', $expression, 2);
            return "<?php if(!empty($value)): ?><div><strong><?= $label ?>:</strong> <?= $value ?></div><?php endif; ?>";
        });
    }
}
