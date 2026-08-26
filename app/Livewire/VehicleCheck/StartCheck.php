<?php

namespace App\Livewire\VehicleCheck;

use App\Jobs\ImportListing;
use App\Models\ListingImage;
use App\Models\ListingImport;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Discounts\DiscountCodeService;
use App\Services\Ordering\VehicleCheckOrderService;
use App\Services\Pricing\PricingService;
use App\Services\RegistrationLookup\VehicleSpecPreviewProvider;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
class StartCheck extends Component
{
    use WithFileUploads;

    public string $step = 'choose';

    public string $registration = '';

    public ?string $type = null;

    public ?array $vehiclePreview = null;

    public string $previewStatus = 'idle';

    public bool $vehicleConfirmed = false;

    public ?int $mileage = null;

    public string $listing_url = '';

    public string $auction_name = '';

    public ?float $current_bid = null;

    public ?float $asking_price = null;

    public string $listing_description = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $images = [];

    // idle, importing, unavailable, found, failed
    public string $importStatus = 'idle';

    public ?int $listingImportId = null;

    public ?array $importPreview = null;

    /** @var array<int, int> */
    public array $importedImageIds = [];

    /** @var array<string, mixed> */
    public array $importedFieldsSnapshot = [];

    public string $discount_code = '';

    // idle, found, invalid
    public string $discountStatus = 'idle';

    public ?array $discountPreview = null;

    public function mount(): void
    {
        $this->registration = strtoupper((string) request()->query('registration', ''));

        // A registration arriving via query string was already looked up and
        // confirmed on the landing page — don't ask the user to confirm the
        // same vehicle a second time.
        if ($this->registration !== '') {
            $this->vehicleConfirmed = true;
        }
    }

    public function updatedRegistration(): void
    {
        // Typing a different plate invalidates any earlier confirmation.
        $this->vehiclePreview = null;
        $this->previewStatus = 'idle';
        $this->vehicleConfirmed = false;
    }

    /**
     * Runs the cheap "is this your vehicle?" lookup exactly once, right
     * after the registration is entered — never on every keystroke, and
     * never again once confirmed, since a real lookup costs real money.
     */
    public function lookupVehicle(): void
    {
        $this->validate(['registration' => ['required', 'string', 'min:2', 'max:10']]);

        $this->registration = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->registration));
        $this->vehiclePreview = null;
        $this->previewStatus = 'loading';

        try {
            $result = app(VehicleSpecPreviewProvider::class)->preview($this->registration);

            if ($result === null) {
                $this->previewStatus = 'not_found';
                $this->vehicleConfirmed = true;

                return;
            }

            $this->vehiclePreview = [
                'make' => $result->make,
                'model' => $result->model,
                'colour' => $result->colour,
                'fuel_type' => $result->fuelType,
                'year' => $result->yearOfManufacture,
                'engine_capacity' => $result->engineCapacity,
                'mot_status' => $result->motStatus,
                'tax_status' => $result->taxStatus,
            ];
            $this->previewStatus = 'found';
        } catch (Throwable) {
            $this->previewStatus = 'unavailable';
            $this->vehicleConfirmed = true;
        }
    }

    public function confirmVehicle(bool $isCorrect): void
    {
        if (! $isCorrect) {
            $this->vehiclePreview = null;
            $this->previewStatus = 'idle';
            $this->vehicleConfirmed = false;

            return;
        }

        $this->vehicleConfirmed = true;
    }

    public function usingMockPreviewData(): bool
    {
        return config('valecheck.registration_lookup.provider') === 'mock';
    }

    /**
     * Attempts to pre-fill listing details from a pasted public URL. Never
     * blocks the rest of the form — on any failure the existing manual
     * fields remain exactly as usable as before this method ran.
     */
    public function importListing(): void
    {
        $this->validate(['listing_url' => ['required', 'url', 'max:500']]);

        if (! config('valecheck.listing_import.enabled')) {
            $this->importStatus = 'unavailable';

            return;
        }

        $urlHash = sha1($this->listing_url);
        $cacheHours = (int) config('valecheck.listing_import.cache_hours', 6);

        $cached = ListingImport::query()
            ->where('url_hash', $urlHash)
            ->where('created_at', '>=', now()->subHours($cacheHours))
            ->whereIn('status', ListingImport::TERMINAL_STATUSES)
            ->latest()
            ->first();

        if ($cached) {
            $this->listingImportId = $cached->id;
            $this->applyImportResult($cached);

            return;
        }

        $listingImport = ListingImport::create([
            'url' => $this->listing_url,
            'url_hash' => $urlHash,
            'domain' => strtolower((string) parse_url($this->listing_url, PHP_URL_HOST)),
            'provider' => 'pending',
            'status' => ListingImport::STATUS_PENDING,
            'user_id' => auth()->id(),
        ]);

        $this->listingImportId = $listingImport->id;
        $this->importStatus = 'importing';
        $this->importPreview = null;

        ImportListing::dispatch($listingImport->id);
    }

    /**
     * Polled from the view (wire:poll) while an import is in progress.
     */
    public function refreshImportStatus(): void
    {
        if (! $this->listingImportId || $this->importStatus !== 'importing') {
            return;
        }

        $listingImport = ListingImport::find($this->listingImportId);

        if ($listingImport && $listingImport->isTerminal()) {
            $this->applyImportResult($listingImport);
        }
    }

    private function applyImportResult(ListingImport $listingImport): void
    {
        $this->importStatus = in_array($listingImport->status, [ListingImport::STATUS_SUCCESS, ListingImport::STATUS_PARTIAL], true)
            ? 'found'
            : 'failed';

        $this->importPreview = [
            'status' => $listingImport->status,
            'fields' => $listingImport->data ?? [],
            'image_count' => $listingImport->image_count_found,
            'images_capped' => $listingImport->images_capped,
            'error_message' => $listingImport->error_message,
        ];
    }

    /**
     * Copies the commerce-relevant imported fields into the same manual
     * form fields the customer could otherwise type by hand — the manual
     * fields are never hidden or replaced, only pre-filled.
     */
    public function useImportedData(): void
    {
        if (! $this->listingImportId || ! $this->importPreview) {
            return;
        }

        $fields = $this->importPreview['fields'];
        $value = fn (string $key) => $fields[$key]['value'] ?? null;

        if ($mileage = $value('mileage')) {
            $this->mileage = (int) $mileage;
        }

        if ($askingPrice = $value('asking_price')) {
            $this->asking_price = (float) $askingPrice;
        }

        if ($currentBid = $value('current_bid')) {
            $this->current_bid = (float) $currentBid;
        }

        if ($description = ($value('description') ?? $value('title'))) {
            $this->listing_description = (string) $description;
        }

        if ($this->auction_name === '') {
            $listingImport = ListingImport::find($this->listingImportId);
            $this->auction_name = $listingImport?->domain ?? '';
        }

        $this->importedFieldsSnapshot = [
            'mileage' => $this->mileage,
            'auction_name' => $this->auction_name,
            'current_bid' => $this->current_bid,
            'asking_price' => $this->asking_price,
            'listing_description' => $this->listing_description,
        ];

        $this->importedImageIds = ListingImage::query()
            ->where('listing_import_id', $this->listingImportId)
            ->where('status', ListingImage::STATUS_DOWNLOADED)
            ->pluck('id')
            ->all();
    }

    /**
     * Compares the current form values against the snapshot taken when
     * "use imported data" was clicked, so each field can be labelled
     * imported/manual transparently in the report — without a full
     * per-keystroke dirty-tracking system.
     */
    private function buildListingDataSources(): ?array
    {
        if ($this->importedFieldsSnapshot === []) {
            return null;
        }

        $sources = [];

        foreach ($this->importedFieldsSnapshot as $field => $snapshotValue) {
            $sources[$field] = ((string) $this->{$field} === (string) $snapshotValue) ? 'imported' : 'manual';
        }

        return $sources;
    }

    public function updatedDiscountCode(): void
    {
        $this->discountStatus = 'idle';
        $this->discountPreview = null;
    }

    /**
     * Validated immediately so the customer sees the discounted price
     * before paying, not as a surprise at checkout — the real,
     * money-affecting check happens again server-side in
     * StripeCheckoutService, which never trusts this client-side state.
     */
    public function applyDiscountCode(DiscountCodeService $discounts, PricingService $pricing): void
    {
        $this->validate(['discount_code' => ['required', 'string', 'max:50']]);

        $discount = $discounts->find($this->discount_code, (string) $this->type);

        if (! $discount) {
            $this->discountStatus = 'invalid';
            $this->discountPreview = null;

            return;
        }

        $originalPrice = $pricing->forProduct((string) $this->type);

        $this->discount_code = $discount->code;
        $this->discountStatus = 'found';
        $this->discountPreview = [
            'original_price' => $originalPrice->gross,
            'discounted_price' => $discounts->apply($discount, $originalPrice->gross),
        ];
    }

    /**
     * Choosing a product no longer re-checks the vehicle — that already
     * happened once, above. ValeCheck goes straight to payment; Plus and
     * Rebuild both collect listing details first (only Rebuild also asks
     * for photographs, since only Rebuild does AI damage analysis).
     */
    public function choose(string $type): void
    {
        // Rebuild is temporarily hidden from launch — ignore the selection
        // rather than advancing, since the card that calls this isn't shown
        // either, but Livewire's update protocol could still be used to
        // call this method directly.
        if ($type === VehicleCheck::TYPE_REBUILD && ! config('valecheck.rebuild_enabled')) {
            return;
        }

        $this->type = $type;
        $this->step = $type === VehicleCheck::TYPE_CHECK ? 'confirm' : 'details';
    }

    /**
     * Anyone can browse pricing and choose a product without an account —
     * signing up is only required at the point of actually getting a report.
     */
    public function submit(VehicleCheckOrderService $orderService): void
    {
        // Belt-and-braces: the real block is here, not just in choose() —
        // this is what actually prevents a Rebuild check being created.
        if ($this->type === VehicleCheck::TYPE_REBUILD && ! config('valecheck.rebuild_enabled')) {
            $this->step = 'choose';

            return;
        }

        if (! auth()->check()) {
            session(['url.intended' => request()->fullUrl()]);
            $this->step = 'auth-required';

            return;
        }

        $this->validate([
            'registration' => ['required', 'string', 'min:2', 'max:10'],
            'mileage' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'listing_url' => ['nullable', 'url', 'max:500'],
            'auction_name' => ['nullable', 'string', 'max:255'],
            'current_bid' => ['nullable', 'numeric', 'min:0'],
            'asking_price' => ['nullable', 'numeric', 'min:0'],
            'listing_description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:'.config('valecheck.ai.max_images')],
            'images.*' => ['image', 'max:8192'],
        ]);

        $check = $orderService->submit(auth()->user(), $this->type, [
            'registration' => $this->registration,
            'mileage' => $this->mileage,
            'listing_url' => $this->listing_url ?: null,
            'auction_name' => $this->auction_name ?: null,
            'current_bid' => $this->current_bid,
            'asking_price' => $this->asking_price,
            'listing_description' => $this->listing_description ?: null,
            'listing_import_id' => $this->listingImportId,
            'listing_data_sources' => $this->buildListingDataSources(),
            // Only carried through once actually validated by applyDiscountCode()
            // — StripeCheckoutService re-validates it again regardless.
            'discount_code' => $this->discountStatus === 'found' ? $this->discount_code : null,
            'images' => $this->type === VehicleCheck::TYPE_REBUILD ? $this->storeImages() : [],
            'imported_image_ids' => $this->type === VehicleCheck::TYPE_REBUILD ? $this->importedImageIds : [],
        ]);

        if ($check->funding_source === 'purchase') {
            $this->redirect(route('checkout.vehicle-check', $check), navigate: false);

            return;
        }

        $this->redirect(route('vehicle-checks.show', $check), navigate: false);
    }

    public function importedImages()
    {
        return $this->importedImageIds === []
            ? collect()
            : ListingImage::whereIn('id', $this->importedImageIds)->get();
    }

    /**
     * @return array<int, string>
     */
    private function storeImages(): array
    {
        return collect($this->images)
            ->map(fn ($image) => $image->store('vehicle-check-uploads', 'local'))
            ->values()
            ->all();
    }

    public function render(PricingService $pricing)
    {
        return view('livewire.vehicle-check.start-check', [
            'checkPrice' => $pricing->forCheck(),
            'plusPrice' => $pricing->forPlus(),
            'rebuildPrice' => $pricing->forRebuild(),
            'plusBalance' => auth()->check() ? app(CreditLedgerService::class)->balance(auth()->user(), VehicleCheck::TYPE_PLUS) : 0,
            'rebuildBalance' => auth()->check() ? app(CreditLedgerService::class)->balance(auth()->user(), VehicleCheck::TYPE_REBUILD) : 0,
        ]);
    }
}
