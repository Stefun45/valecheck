<?php

namespace App\Http\Controllers;

use App\Services\Ai\PlateScanService;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\RegistrationLookup\PlateScanGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated endpoint backing the mobile-WEB "scan number
 * plate" camera upload on the start-check page (see start-check.blade.php)
 * — the web equivalent of the native app's on-device PlateScanner plugin,
 * for visitors using an ordinary mobile browser rather than the app.
 *
 * Deliberately simple and synchronous: no queued job, no database row for
 * the attempt itself (only an AiUsage log, written inside PlateScanService)
 * — this needs to feel close to instant, and there's nothing here worth
 * persisting beyond that cost-visibility log.
 */
class PlateScanController extends Controller
{
    /**
     * Matches the current UK registration format only (two letters, two
     * digits, three letters — e.g. "AB12CDE", introduced September 2001),
     * exactly like the native app's UKPlateMatcher.swift. Claude's raw
     * output is never trusted blindly: only a result that passes this same
     * check is ever returned to the browser or filled into the form.
     */
    private const UK_PLATE_FORMAT = '/^[A-Z]{2}[0-9]{2}[A-Z]{3}$/';

    public function __invoke(Request $request, PlateScanService $scanner, PlateScanGuard $guard): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        if ($guard->tooManyAttempts()) {
            return response()->json([
                'success' => false,
                'reason' => 'rate_limited',
                'message' => "You've made a lot of scan attempts in a short space of time. Please type your registration instead.",
            ], 429);
        }

        $guard->recordAttempt();

        $photo = $request->file('photo');

        $plate = $scanner->scan(
            file_get_contents($photo->getRealPath()),
            $photo->getMimeType() ?: 'image/jpeg',
        );

        if ($plate === null) {
            return response()->json([
                'success' => false,
                'reason' => 'not_read',
                'message' => "We couldn't read a number plate in that photo. Please try again or type it in.",
            ]);
        }

        $normalised = MotHistoryAndTaxStatusFetcher::normalise($plate);

        if (! preg_match(self::UK_PLATE_FORMAT, $normalised)) {
            return response()->json([
                'success' => false,
                'reason' => 'invalid_format',
                'message' => "We couldn't read a clear number plate in that photo. Please try again or type it in.",
            ]);
        }

        return response()->json([
            'success' => true,
            'registration' => $normalised,
        ]);
    }
}
