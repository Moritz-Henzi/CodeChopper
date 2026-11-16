<?php

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('guests cannot access the trainings', function () {
    $training = Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);

    $this->getJson(route('api.v1.trainings.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.trainings.show', $training))->assertUnauthorized();
});

test('the trainings index lists all trainings ordered by name', function () {
    Training::create(['name' => 'Bravo', 'validity_months' => null, 'renewal_months' => 0, 'end_of_month_expiry' => false]);
    Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => true]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.trainings.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Alpha')
        ->assertJsonPath('data.0.validity_months', 12)
        ->assertJsonPath('data.0.end_of_month_expiry', true)
        ->assertJsonPath('data.1.name', 'Bravo')
        ->assertJsonPath('data.1.validity_months', null);
});

test('a single training can be shown', function () {
    $training = Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.trainings.show', $training))
        ->assertOk()
        ->assertJson(['data' => [
            'id' => $training->id,
            'name' => 'Alpha',
            'validity_months' => 12,
            'renewal_months' => 1,
            'end_of_month_expiry' => false,
        ]]);
});

test('showing an unknown training returns not found', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.trainings.show', 999))->assertNotFound();
});
