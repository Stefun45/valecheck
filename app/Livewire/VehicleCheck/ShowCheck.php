<?php

namespace App\Livewire\VehicleCheck;

use App\Models\VehicleCheck;
use App\Services\SalvageAuction\SalvageAuctionImageProvider;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class ShowCheck extends Component
{
    public VehicleCheck $vehicleCheck;

    public string $vinToVerify = '';

    public ?bool $vinMatchResult = null;

    public function mount(VehicleCheck $vehicleCheck): void
    {
        $this->authorize('view', $vehicleCheck);

        $this->vehicleCheck = $vehicleCheck;
    }

    public function isProcessing(): bool
    {
        return in_array($this->vehicleCheck->fresh()->status, [
            VehicleCheck::STATUS_PENDING,
            VehicleCheck::STATUS_PROCESSING,
        ], true);
    }

    /**
     * Lets a customer confirm the V5C/dashboard VIN in front of them
     * matches what the report holds, without ever sending the real VIN
     * to the browser — only this boolean result leaves the server.
     */
    public function verifyVin(): void
    {
        $submitted = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $this->vinToVerify));
        $realVin = $this->vehicleCheck->vehicle->vin;

        $this->vinMatchResult = $submitted !== '' && $realVin !== null && strtoupper($realVin) === $submitted;
    }

    /**
     * A live, fetched-right-now link to this report's salvage-auction
     * photos for each found record that has a recordId - see
     * SalvageAuctionImageProvider and the removal of
     * RetrieveSalvageAuctionImages. One Auto's thirdpartyimages URL
     * expires after ~12 hours, far shorter than a report's up-to-30-day
     * retention window, so pre-fetching once and storing the URL (the
     * previous approach) would often hand a customer a dead link; fetching
     * live at view time never can.
     *
     * #[Computed] on its own (no `persist`/`cache`) only memoizes for the
     * lifetime of the single PHP object that handles one Livewire request -
     * confirmed by reading BaseComputed::handleMagicGet() in this app's
     * installed Livewire version, whose default (uncached) branch just
     * stores the value on a property of the Attribute instance. verifyVin()
     * is a separate Livewire network round-trip that hydrates a brand new
     * component instance, so without `persist` it would trigger a second
     * live (and potentially billable) fetch on every interaction with this
     * same report. `persist: true` instead caches via Laravel's Cache,
     * keyed by this component's own Livewire id (see
     * BaseComputed::generatePersistedKey()), so the result survives across
     * further interactions with this one mounted report, while a genuinely
     * fresh page load (a new Livewire id) still fetches a fresh link.
     *
     * A failed or slow One Auto call degrades to an empty array for that
     * record rather than breaking the whole report page - the same
     * "additive provenance, not safety-critical" philosophy as everywhere
     * else in this feature.
     *
     * @return array<int, array<int, string>> image/listing-page URLs keyed
     *                                        by the record's index in
     *                                        salvageAuctionCheck->records.
     */
    #[Computed(persist: true)]
    public function salvageAuctionImageLinks(): array
    {
        $salvageCheck = $this->vehicleCheck->salvageAuctionCheck;

        if (! $salvageCheck || ! $salvageCheck->record_found) {
            return [];
        }

        $provider = app(SalvageAuctionImageProvider::class);
        $links = [];

        foreach ($salvageCheck->records as $index => $record) {
            $recordId = $record['recordId'] ?? null;

            if ($recordId === null) {
                continue;
            }

            try {
                $links[$index] = $provider->fetchImages((string) $recordId, $this->vehicleCheck->id);
            } catch (Throwable) {
                $links[$index] = [];
            }
        }

        return $links;
    }

    public function render()
    {
        $this->vehicleCheck->refresh();

        $view = match (true) {
            $this->vehicleCheck->status === VehicleCheck::STATUS_FAILED => 'livewire.vehicle-check.partials.failed',
            $this->vehicleCheck->status === VehicleCheck::STATUS_COMPLETED && $this->vehicleCheck->isRebuild() => 'livewire.vehicle-check.partials.rebuild-report',
            $this->vehicleCheck->status === VehicleCheck::STATUS_COMPLETED && $this->vehicleCheck->isPlus() => 'livewire.vehicle-check.partials.plus-report',
            $this->vehicleCheck->status === VehicleCheck::STATUS_COMPLETED => 'livewire.vehicle-check.partials.check-report',
            default => 'livewire.vehicle-check.partials.progress',
        };

        return view('livewire.vehicle-check.show-check', ['contentView' => $view]);
    }
}
