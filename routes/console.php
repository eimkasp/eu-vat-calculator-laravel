<?php

use App\Jobs\GenerateVatRateChanges;
use App\Jobs\UpdateVatRates;
use App\Jobs\VerifyVatRatesIntegrity;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| VAT Data Refresh Schedule
|--------------------------------------------------------------------------
| Weekly: Download latest VAT rates from kdeldycke/vat-rates GitHub repo
| Daily:  Verify integrity between vat_rates and countries tables
| Daily:  Generate change records for the VAT history/changelog
*/

Schedule::job(new UpdateVatRates)->weeklyOn(1, '03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/vat-refresh.log'));

Schedule::job(new VerifyVatRatesIntegrity)->dailyAt('04:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new GenerateVatRateChanges)->dailyAt('04:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('vat-changes:notify-subscribers')->dailyAt('05:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/vat-change-notifications.log'));

// Draw the social images of the country pages that are missing, after the rate jobs above: a rate
// change gives a country a new image URL (App\Support\Seo\CountryOgImage).
Schedule::command('og-image:countries')->dailyAt('05:30')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/og-images.log'));
