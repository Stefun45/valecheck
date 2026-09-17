{{-- Shared by check-report.blade.php and plus-report.blade.php. Never
     exposes the real VIN - verifyVin() on ShowCheck only ever returns a
     match/no-match boolean.

     $vinToVerify/$vinMatchResult are ShowCheck's own public properties,
     auto-exposed to the view by Livewire - they're only ever actually
     set when this partial renders as part of that live component. The
     public /sample-report page renders plus-report.blade.php directly
     via a plain Blade include (no Livewire component behind it, see
     SampleReportController/sample-report.blade.php), so wire:submit
     has nothing to call there - pass interactive: false from that
     context to skip the (otherwise dead) form entirely rather than
     show a control that can't do anything, and default the two
     properties defensively so this never hard-crashes regardless of
     what does or doesn't get passed in. --}}
@php
    $interactive ??= true;
    $vinToVerify ??= '';
    $vinMatchResult ??= null;
@endphp
@if ($interactive)
    <div class="mt-3 pt-3 border-t border-gray-100">
        <label for="vinToVerify" class="block text-xs text-gray-500 mb-1.5">Confirm the VIN on the V5C or dashboard matches:</label>
        <form wire:submit="verifyVin" class="flex gap-2">
            <input
                wire:model="vinToVerify"
                id="vinToVerify"
                type="text"
                maxlength="17"
                placeholder="17-digit VIN"
                class="flex-1 min-w-0 text-xs font-mono uppercase rounded-md border-gray-300 focus:border-vale-red focus:ring-vale-red py-1.5"
            >
            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-full text-xs font-semibold text-gray-600 hover:bg-gray-50 shrink-0">
                Verify
            </button>
        </form>
        @if ($vinMatchResult === true)
            <p class="text-xs text-green-700 mt-1.5">&#10003; That VIN matches this report.</p>
        @elseif ($vinMatchResult === false)
            <p class="text-xs text-vale-red mt-1.5">That VIN does not match this report - double-check what you entered, or treat this as a warning sign.</p>
        @endif
    </div>
@endif
