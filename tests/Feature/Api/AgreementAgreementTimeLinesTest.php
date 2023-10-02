<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Agreement;
use App\Models\AgreementTimeLine;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgreementAgreementTimeLinesTest extends TestCase
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
    public function it_gets_agreement_agreement_time_lines()
    {
        $agreement = Agreement::factory()->create();
        $agreementTimeLines = AgreementTimeLine::factory()
            ->count(2)
            ->create([
                'agreement_id' => $agreement->id,
            ]);

        $response = $this->getJson(
            route('api.agreements.agreement-time-lines.index', $agreement)
        );

        $response->assertOk()->assertSee($agreementTimeLines[0]->transcription);
    }

    /**
     * @test
     */
    public function it_stores_the_agreement_agreement_time_lines()
    {
        $agreement = Agreement::factory()->create();
        $data = AgreementTimeLine::factory()
            ->make([
                'agreement_id' => $agreement->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.agreements.agreement-time-lines.store', $agreement),
            $data
        );

        unset($data['transcription']);
        unset($data['agreement_id']);

        $this->assertDatabaseHas('agreement_time_lines', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $agreementTimeLine = AgreementTimeLine::latest('id')->first();

        $this->assertEquals($agreement->id, $agreementTimeLine->agreement_id);
    }
}
