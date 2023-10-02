<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Payment;
use App\Models\Terminal;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TerminalPaymentsTest extends TestCase
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
    public function it_gets_terminal_payments()
    {
        $terminal = Terminal::factory()->create();
        $payments = Payment::factory()
            ->count(2)
            ->create([
                'terminal_id' => $terminal->id,
            ]);

        $response = $this->getJson(
            route('api.terminals.payments.index', $terminal)
        );

        $response->assertOk()->assertSee($payments[0]->customer);
    }

    /**
     * @test
     */
    public function it_stores_the_terminal_payments()
    {
        $terminal = Terminal::factory()->create();
        $data = Payment::factory()
            ->make([
                'terminal_id' => $terminal->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.terminals.payments.store', $terminal),
            $data
        );

        $this->assertDatabaseHas('payments', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $payment = Payment::latest('id')->first();

        $this->assertEquals($terminal->id, $payment->terminal_id);
    }
}
