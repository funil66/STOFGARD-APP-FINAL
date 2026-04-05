<?php

namespace Tests\Feature\Events;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenancyInitialized;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class TenancyEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('tenancy.database.managers.sqlite', \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class);
        $connName = config('tenancy.database.template_tenant_connection') ?? config('database.default');
        if (!config('database.connections.'.$connName)) {
            Config::set('database.connections.'.$connName, config('database.connections.sqlite'));
        }
    }

    public function test_tenant_created_event_is_dispatched()
    {
        Event::fake([TenantCreated::class]);
        $tenant = Tenant::create(['id' => 'test-event-tenant-' . Str::random(5)]);
        Event::assertDispatched(TenantCreated::class, function ($event) use ($tenant) {
            return $event->tenant->id === $tenant->id;
        });
        $tenant->delete();
    }
    
    public function test_tenancy_initialized_event_is_dispatched()
    {
        $tenant = Tenant::create(['id' => 'test-storage-'.Str::random(5)]);
        
        Event::fake([TenancyInitialized::class]);
        
        tenancy()->initialize($tenant);
        
        Event::assertDispatched(TenancyInitialized::class, function ($event) use ($tenant) {
            return $event->tenancy->tenant->id === $tenant->id;
        });
        
        tenancy()->end();
        $tenant->delete();
    }
}
