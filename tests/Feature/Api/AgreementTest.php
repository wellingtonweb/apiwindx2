<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Agreement;

use App\Models\Payment;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgreementTest extends TestCase
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
    public function it_gets_agreements_list()
    {
        $agreements = Agreement::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.agreements.index'));

        $response->assertOk()->assertSee($agreements[0]->customer);
    }

    /**
     * @test
     */
    public function it_stores_the_agreement()
    {
        $data = Agreement::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.agreements.store'), $data);

        unset($data['payment_id']);

        $this->assertDatabaseHas('agreements', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_agreement()
    {
        $agreement = Agreement::factory()->create();

        $payment = Payment::factory()->create();

        $data = [
            'customer' => $this->faker->text(255),
            'amount' => $this->faker->randomNumber(2),
            'billets' => [],
            'os_number' => $this->faker->text(255),
            'valid_thru' => $this->faker->date,
            'status' => 'created',
            'payment_id' => $payment->id,
        ];

        $response = $this->putJson(
            route('api.agreements.update', $agreement),
            $data
        );

        unset($data['payment_id']);

        $data['id'] = $agreement->id;

        $this->assertDatabaseHas('agreements', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_agreement()
    {
        $agreement = Agreement::factory()->create();

        $response = $this->deleteJson(
            route('api.agreements.destroy', $agreement)
        );

        $this->assertDeleted($agreement);

        $response->assertNoContent();
    }
}
