<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Terminal;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TerminalTest extends TestCase
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
    public function it_gets_terminals_list()
    {
        $terminals = Terminal::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.terminals.index'));

        $response->assertOk()->assertSee($terminals[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_terminal()
    {
        $data = Terminal::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.terminals.store'), $data);

        $this->assertDatabaseHas('terminals', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_terminal()
    {
        $terminal = Terminal::factory()->create();

        $data = [
            'name' => $this->faker->name,
            'ip_address' => $this->faker->ipv4,
            'remote_id' => $this->faker->text(255),
            'remote_password' => $this->faker->text(255),
            'active' => $this->faker->boolean,
            'responsible_name' => $this->faker->text(255),
            'contact_primary' => $this->faker->text(255),
            'contact_secondary' => $this->faker->text(255),
            'street' => $this->faker->streetName,
            'number' => $this->faker->randomNumber,
            'complement' => $this->faker->text(255),
            'district' => $this->faker->text(255),
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'zipcode' => $this->faker->text(255),
            'paygo_id' => $this->faker->text(255),
            'paygo_login' => $this->faker->text(255),
            'paygo_password' => $this->faker->text(255),
        ];

        $response = $this->putJson(
            route('api.terminals.update', $terminal),
            $data
        );

        $data['id'] = $terminal->id;

        $this->assertDatabaseHas('terminals', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_terminal()
    {
        $terminal = Terminal::factory()->create();

        $response = $this->deleteJson(
            route('api.terminals.destroy', $terminal)
        );

        $this->assertSoftDeleted($terminal);

        $response->assertNoContent();
    }
}
