<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_tokenize_manage_and_delete_saved_test_cards(): void
    {
        Http::fake([
            'http://payment-svc:8081/tokenize' => Http::response([
                'token' => 'tok_ok_account_card',
                'card_type' => 'visa',
                'last_four' => '4242',
                'expiration' => '12/30',
            ], 201),
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('account.cards.store'), [
            'card_number' => '4242424242424242',
            'expiration' => '12/30',
            'cvv' => '123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('credit_cards', [
            'user_id' => $user->id,
            'card_token' => 'tok_ok_account_card',
            'last_four' => '4242',
            'is_default' => true,
        ]);
        $this->assertDatabaseMissing('credit_cards', ['card_token' => '4242424242424242']);

        $second = CreditCard::query()->create([
            'user_id' => $user->id,
            'card_type' => 'visa',
            'last_four' => '3220',
            'card_token' => 'tok_auth_second',
            'expiration' => '11/31',
            'is_default' => false,
        ]);
        $this->actingAs($user)->patch(route('account.cards.update', $second), ['is_default' => '1'])->assertRedirect();
        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($user->cards()->where('card_token', 'tok_ok_account_card')->firstOrFail()->is_default);

        $this->actingAs($user)->delete(route('account.cards.destroy', $second))->assertRedirect();
        $this->assertDatabaseMissing('credit_cards', ['id' => $second->id]);
        $this->assertTrue($user->cards()->firstOrFail()->is_default);
    }

    public function test_customer_cannot_change_another_customers_card(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $card = CreditCard::query()->create([
            'user_id' => $owner->id,
            'card_type' => 'visa',
            'last_four' => '4242',
            'card_token' => 'tok_owner_only',
            'expiration' => '12/30',
            'is_default' => true,
        ]);

        $this->actingAs($other)->patch(route('account.cards.update', $card), ['is_default' => '1'])->assertForbidden();
        $this->actingAs($other)->delete(route('account.cards.destroy', $card))->assertForbidden();
    }
}
