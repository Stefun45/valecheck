<?php

namespace Tests\Unit;

use App\DataTransferObjects\VehicleImageData;
use App\Jobs\RetrieveVehicleImage;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\VehicleImagery\VehicleImageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetrieveVehicleImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_found_image_is_stored_and_the_check_is_updated(): void
    {
        Storage::fake('local');

        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->app->instance(VehicleImageProvider::class, new class implements VehicleImageProvider
        {
            public function fetch(string $registration, ?string $colour, ?int $vehicleCheckId = null): VehicleImageData
            {
                return new VehicleImageData(available: true, contents: 'fake-bytes', mimeType: 'image/png');
            }
        });

        (new RetrieveVehicleImage($check->id))->handle($this->app->make(VehicleImageProvider::class));

        $check->refresh();
        $this->assertSame('local', $check->vehicle_image_disk);
        $this->assertSame("reports/{$check->id}/vehicle-image.png", $check->vehicle_image_path);
        Storage::disk('local')->assertExists($check->vehicle_image_path);
        $this->assertSame('fake-bytes', Storage::disk('local')->get($check->vehicle_image_path));
    }

    public function test_an_unavailable_image_leaves_the_check_untouched(): void
    {
        Storage::fake('local');

        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->app->instance(VehicleImageProvider::class, new class implements VehicleImageProvider
        {
            public function fetch(string $registration, ?string $colour, ?int $vehicleCheckId = null): VehicleImageData
            {
                return new VehicleImageData(available: false);
            }
        });

        (new RetrieveVehicleImage($check->id))->handle($this->app->make(VehicleImageProvider::class));

        $check->refresh();
        $this->assertNull($check->vehicle_image_disk);
        $this->assertNull($check->vehicle_image_path);
        $this->assertFalse($check->hasVehicleImage());
    }
}
