<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Payment;
use App\Models\Agreement;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentAgreementsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['email' => 'admin@admin.com']);

        Sanctum::actingAs($user, [], 'web');

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_gets_payment_agreements()
    {
        $payment = Payment::factory()->create();
        $agreements = Agreement::factory()
            ->count(2)
            ->create([
                'payment_id' => $payment->id,
            ]);

        $response = $this->getJson(
            route('api.payments.agreements.index', $payment)
        );

        $response->assertOk()->assertSee($agreements[0]->customer);
    }

    /**
     * @test
     */
    public function it_stores_the_payment_agreements()
    {
        $payment = Payment::factory()->create();
        $data = Agreement::factory()
            ->make([
                'payment_id' => $payment->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.payments.agreements.store', $payment),
            $data
        );

        unset($data['payment_id']);

        $this->assertDatabaseHas('agreements', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $agreement = Agreement::latest('id')->first();

        $this->assertEquals($payment->id, $agreement->payment_id);
    }
}
