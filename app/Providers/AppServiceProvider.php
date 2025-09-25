<?php

namespace App\Providers;

use App\Models\Location;
use App\Models\Partner;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Treatment;
use App\Models\TreatmentTable;
use App\Observers\LocationObserver;
use App\Observers\PatientObserver;
use App\Observers\RoomObserver;
use App\Observers\TreatmentObserver;
use App\Observers\TreatmentTableObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        Relation::morphMap([
            'location' => Location::class,
            'room' => Room::class,
            'professional' => Partner::class,
        ]);

        Treatment::observe(TreatmentObserver::class);
        TreatmentTable::observe(TreatmentTableObserver::class);
        Location::observe(LocationObserver::class);
        Room::observe(RoomObserver::class);
        Patient::observe(PatientObserver::class);
    }
}
