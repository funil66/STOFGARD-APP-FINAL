<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            // If a remote driver URL is provided (e.g., Selenium service), skip starting a chromedriver locally
            $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL');

            if ($driverUrl) {
                // Do not start local chromedriver; tests will use the provided remote WebDriver
                echo "DUSK_DRIVER_URL is set ({$driverUrl}), skipping startChromeDriver\n";
            } else {
                static::startChromeDriver(['--port=9515']);
            }
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $base = collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ]);

        $base = $base->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        });

        // Allow passing extra Chrome args via DUSK_CHROME_ARGS env var (space separated)
        if ($extra = ($_ENV['DUSK_CHROME_ARGS'] ?? env('DUSK_CHROME_ARGS'))) {
            $extraArgs = collect(explode(' ', $extra))->filter()->values();
            $base = $base->merge($extraArgs);
        }

        $options = (new ChromeOptions)->addArguments($base->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
