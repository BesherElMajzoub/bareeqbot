<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests assert behavior, not built assets — stub the Vite
        // directive so page renders don't require a compiled manifest.
        $this->withoutVite();

        // Roles/permissions are foundational reference data (registration
        // assigns the owner role). Seed them for any test that refreshes the
        // database, covering both Pest and class-based tests.
        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
