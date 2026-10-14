<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Reverses 2026_10_12_134227_create_salvage_auction_images_table.php. That
 * migration has already run in production, so it is left alone rather than
 * edited (this codebase's convention for an already-applied migration) -
 * this is a new migration that undoes it instead.
 *
 * The whole "download and permanently store salvage-auction photos"
 * approach it supported was superseded before the feature saw any real
 * customer use: testing against a real vehicle (Lot ID 59749706) proved
 * the One Auto third-party-images URL is a link to a JavaScript-rendered
 * listing page, not a direct image file, so a server-side fetch can never
 * retrieve the actual photos - see OneAutoSalvageAuctionImageProvider and
 * ShowCheck::salvageAuctionImageLinks() for the replacement, which links
 * out to a freshly-fetched URL live at report-view time instead.
 *
 * The one row this table ever picked up in production holds exactly that
 * failure mode - a mis-saved HTML page stored at
 * salvage-auction/32/0-0.jpg, not a real photo - so it is not customer
 * data worth preserving. Each row's stored file is still deleted properly
 * below rather than assumed to be the only one.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('salvage_auction_images')->orderBy('id')->each(function (object $row) {
            try {
                Storage::disk($row->disk)->delete($row->path);
            } catch (Throwable) {
                // A missing disk/file at this point changes nothing about
                // the goal here - the table is being dropped regardless -
                // so this must never block the migration from completing.
            }
        });

        Schema::dropIfExists('salvage_auction_images');
    }

    public function down(): void
    {
        // Deliberately not recreated - see the class doc comment. A
        // rollback of this migration has nothing meaningful to restore to;
        // run the original create-table migration again if this table is
        // ever genuinely needed.
    }
};
