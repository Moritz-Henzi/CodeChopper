<?php

use App\Models\JobTitle;
use App\Models\Training;
use App\Models\TrainingCompletion;
use App\Models\User;
use App\Services\TrainingStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo('2026-11-16 10:00:00');

    $this->jobTitle = JobTitle::create(['name' => 'Pilot']);
    $this->training = Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $this->jobTitle->trainings()->attach($this->training);

    $this->crewMember = User::factory()->create();
    $this->crewMember->jobTitles()->attach($this->jobTitle);

    $this->cacheKey = app(TrainingStatusService::class)->matrixCacheKey();

    Sanctum::actingAs($this->crewMember);
});

test('the dashboard matrix is cached for the current day', function () {
    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertJsonPath('data.rows.0.cells.0.status', 'red');

    expect(Cache::has($this->cacheKey))->toBeTrue();

    DB::table('training_completions')->insert([
        'user_id' => $this->crewMember->id,
        'training_id' => $this->training->id,
        'completed_at' => '2026-11-01',
        'renewal_from' => '2027-10-01',
        'valid_until' => '2027-10-31',
    ]);

    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertJsonPath('data.rows.0.cells.0.status', 'red');
});

test('creating a training completion flushes the cached matrix', function () {
    $this->getJson(route('api.v1.dashboard.matrix'))->assertOk();

    TrainingCompletion::create([
        'user_id' => $this->crewMember->id,
        'training_id' => $this->training->id,
        'completed_at' => '2026-11-01',
        'renewal_from' => '2027-10-01',
        'valid_until' => '2027-10-31',
    ]);

    expect(Cache::has($this->cacheKey))->toBeFalse();

    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertJsonPath('data.rows.0.cells.0.status', 'green');
});

test('attaching and detaching job titles flushes the cached matrix', function () {
    $otherJobTitle = JobTitle::create(['name' => 'Administrator']);

    $this->getJson(route('api.v1.dashboard.matrix'))->assertOk();
    $this->crewMember->jobTitles()->attach($otherJobTitle);
    expect(Cache::has($this->cacheKey))->toBeFalse();

    $this->getJson(route('api.v1.dashboard.matrix'))->assertOk();
    $this->crewMember->jobTitles()->detach($otherJobTitle);
    expect(Cache::has($this->cacheKey))->toBeFalse();
});

test('the dashboard matrix is recalculated on the next day', function () {
    TrainingCompletion::create([
        'user_id' => $this->crewMember->id,
        'training_id' => $this->training->id,
        'completed_at' => '2025-11-30',
        'renewal_from' => '2026-11-01',
        'valid_until' => '2026-11-30',
    ]);

    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertJsonPath('data.rows.0.cells.0.days_left', 14);

    $this->travelTo('2026-11-17 08:00:00');

    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertJsonPath('data.rows.0.cells.0.days_left', 13);
});
