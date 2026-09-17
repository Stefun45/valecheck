<?php

namespace Tests\Unit;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLedgerServiceGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_free_grant_increases_the_users_balance(): void
    {
        $user = User::factory()->create();
        $ledger = app(CreditLedgerService::class);

        $ledger->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 3);

        $this->assertSame(3, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
    }

    public function test_a_free_grant_is_recorded_as_its_own_distinct_type_not_a_purchase(): void
    {
        $user = User::factory()->create();

        $transaction = app(CreditLedgerService::class)->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 2, 'Goodwill');

        $this->assertSame(CreditTransaction::TYPE_FREE_GRANT, $transaction->type);
        $this->assertNull($transaction->payment_id);
        $this->assertSame('Goodwill', $transaction->note);
    }

    public function test_a_granted_credit_can_actually_be_consumed_like_a_purchased_one(): void
    {
        $user = User::factory()->create();
        $ledger = app(CreditLedgerService::class);
        $ledger->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 1);

        $this->assertTrue($ledger->hasCredit($user, VehicleCheck::TYPE_PLUS));

        $check = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_PLUS]);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_PLUS, $check);

        $this->assertSame(0, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
    }
}
