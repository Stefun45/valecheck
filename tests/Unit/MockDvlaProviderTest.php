<?php

namespace Tests\Unit;

use App\Services\RegistrationLookup\MockDvlaProvider;
use Tests\TestCase;

class MockDvlaProviderTest extends TestCase
{
    public function test_same_registration_returns_the_same_result_every_time(): void
    {
        $provider = new MockDvlaProvider;

        $first = $provider->preview('AB12 CDE');
        $second = $provider->preview('ab12cde');

        $this->assertSame($first->make, $second->make);
        $this->assertSame($first->colour, $second->colour);
        $this->assertSame($first->yearOfManufacture, $second->yearOfManufacture);
    }

    public function test_never_fabricates_a_model_since_dvla_does_not_provide_one(): void
    {
        // DVLA VES genuinely doesn't return model/derivative data — the
        // mock must stay honest to that, even though the DTO itself now
        // supports a model field for other providers (e.g. VehicleMatic).
        $provider = new MockDvlaProvider;

        $this->assertNull($provider->preview('AB12CDE')->model);
    }
}
