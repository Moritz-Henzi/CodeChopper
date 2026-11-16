<?php

use App\Models\JobTitle;
use App\Models\Training;
use App\Models\TrainingCompletion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Create a training completion for the given user and training.
 */
function completeTraining(User $user, Training $training, string $completedAt, ?string $renewalFrom, ?string $validUntil): TrainingCompletion
{
    return TrainingCompletion::create([
        'user_id' => $user->id,
        'training_id' => $training->id,
        'completed_at' => $completedAt,
        'renewal_from' => $renewalFrom,
        'valid_until' => $validUntil,
    ]);
}

test('guests cannot access the dashboard matrix', function () {
    $this->getJson(route('api.v1.dashboard.matrix'))->assertUnauthorized();
});

test('the dashboard matrix contains the training status of every crew member', function () {
    $this->travelTo('2026-11-16 10:00:00');

    $pilot = JobTitle::create(['name' => 'Pilot']);
    $administrator = JobTitle::create(['name' => 'Administrator']);

    $alpha = Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $bravo = Training::create(['name' => 'Bravo', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $charlie = Training::create(['name' => 'Charlie', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $delta = Training::create(['name' => 'Delta', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $echo = Training::create(['name' => 'Echo', 'validity_months' => null, 'renewal_months' => 0, 'end_of_month_expiry' => false]);
    $foxtrot = Training::create(['name' => 'Foxtrot', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);

    $pilot->trainings()->attach([$alpha->id, $bravo->id, $charlie->id]);
    $administrator->trainings()->attach([$delta->id, $echo->id]);

    $crewMember = User::factory()->create(['name' => 'Thomas Hauser']);
    $crewMember->jobTitles()->attach([$pilot->id, $administrator->id]);

    $userWithoutJobTitle = User::factory()->create(['name' => 'No Job Title']);
    completeTraining($userWithoutJobTitle, $alpha, '2026-01-01', '2026-12-01', '2026-12-31');

    completeTraining($crewMember, $alpha, '2024-01-01', '2024-12-01', '2024-12-31');
    completeTraining($crewMember, $alpha, '2026-01-01', '2026-12-01', '2026-12-31');
    completeTraining($crewMember, $bravo, '2025-11-30', '2026-11-01', '2026-11-30');
    completeTraining($crewMember, $charlie, '2025-11-15', '2026-10-15', '2026-11-15');
    completeTraining($crewMember, $echo, '2020-05-30', null, null);
    completeTraining($crewMember, $foxtrot, '2026-01-01', '2026-12-01', '2026-12-31');

    Sanctum::actingAs($crewMember);

    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'trainings' => [
                ['id' => $alpha->id, 'name' => 'Alpha'],
                ['id' => $bravo->id, 'name' => 'Bravo'],
                ['id' => $charlie->id, 'name' => 'Charlie'],
                ['id' => $delta->id, 'name' => 'Delta'],
                ['id' => $echo->id, 'name' => 'Echo'],
                ['id' => $foxtrot->id, 'name' => 'Foxtrot'],
            ],
            'rows' => [
                [
                    'crew_member' => ['id' => $crewMember->id, 'name' => 'Thomas Hauser', 'job_title' => 'Administrator, Pilot'],
                    'cells' => [
                        ['training_id' => $alpha->id, 'status' => 'green', 'days_left' => 45, 'completed_at' => '2026-01-01', 'renewal_from' => '2026-12-01', 'valid_until' => '2026-12-31'],
                        ['training_id' => $bravo->id, 'status' => 'yellow', 'days_left' => 14, 'completed_at' => '2025-11-30', 'renewal_from' => '2026-11-01', 'valid_until' => '2026-11-30'],
                        ['training_id' => $charlie->id, 'status' => 'red', 'days_left' => -1, 'completed_at' => '2025-11-15', 'renewal_from' => '2026-10-15', 'valid_until' => '2026-11-15'],
                        ['training_id' => $delta->id, 'status' => 'red', 'days_left' => null, 'completed_at' => null, 'renewal_from' => null, 'valid_until' => null],
                        ['training_id' => $echo->id, 'status' => 'green', 'days_left' => null, 'completed_at' => '2020-05-30', 'renewal_from' => null, 'valid_until' => null],
                    ],
                ],
            ],
        ]]);
});

test('a training is still valid on its valid until date', function () {
    $this->travelTo('2026-11-30 23:00:00');

    $pilot = JobTitle::create(['name' => 'Pilot']);
    $training = Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => true]);
    $pilot->trainings()->attach($training);

    $crewMember = User::factory()->create();
    $crewMember->jobTitles()->attach($pilot);
    completeTraining($crewMember, $training, '2025-11-15', '2026-11-01', '2026-11-30');

    Sanctum::actingAs($crewMember);

    $this->getJson(route('api.v1.dashboard.matrix'))
        ->assertOk()
        ->assertJsonPath('data.rows.0.cells.0.status', 'yellow')
        ->assertJsonPath('data.rows.0.cells.0.days_left', 0);
});

test('guests cannot access the training status of a crew member', function () {
    $crewMember = User::factory()->create();

    $this->getJson(route('api.v1.crew-members.show', $crewMember))->assertUnauthorized();
});

test('the training status of a crew member only contains their required trainings', function () {
    $this->travelTo('2026-11-16 10:00:00');

    $pilot = JobTitle::create(['name' => 'Pilot']);
    $administrator = JobTitle::create(['name' => 'Administrator']);
    $flightAttendant = JobTitle::create(['name' => 'Flight Attendant']);

    $alpha = Training::create(['name' => 'Alpha', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $bravo = Training::create(['name' => 'Bravo', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $charlie = Training::create(['name' => 'Charlie', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);
    $delta = Training::create(['name' => 'Delta', 'validity_months' => 12, 'renewal_months' => 1, 'end_of_month_expiry' => false]);

    $pilot->trainings()->attach([$alpha->id, $bravo->id]);
    $administrator->trainings()->attach([$bravo->id, $charlie->id]);
    $flightAttendant->trainings()->attach($delta->id);

    $crewMember = User::factory()->create(['name' => 'Thomas Hauser']);
    $crewMember->jobTitles()->attach([$pilot->id, $administrator->id]);

    $otherCrewMember = User::factory()->create();
    $otherCrewMember->jobTitles()->attach($flightAttendant);
    completeTraining($otherCrewMember, $alpha, '2026-01-01', '2026-12-01', '2026-12-31');

    completeTraining($crewMember, $alpha, '2024-01-01', '2024-12-01', '2024-12-31');
    completeTraining($crewMember, $alpha, '2026-01-01', '2026-12-01', '2026-12-31');
    completeTraining($crewMember, $bravo, '2025-11-30', '2026-11-01', '2026-11-30');
    completeTraining($crewMember, $delta, '2026-01-01', '2026-12-01', '2026-12-31');

    Sanctum::actingAs($otherCrewMember);

    $this->getJson(route('api.v1.crew-members.show', $crewMember))
        ->assertOk()
        ->assertExactJson(['data' => [
            'crew_member' => ['id' => $crewMember->id, 'name' => 'Thomas Hauser', 'job_title' => 'Administrator, Pilot'],
            'trainings' => [
                ['id' => $alpha->id, 'name' => 'Alpha'],
                ['id' => $bravo->id, 'name' => 'Bravo'],
                ['id' => $charlie->id, 'name' => 'Charlie'],
            ],
            'cells' => [
                ['training_id' => $alpha->id, 'status' => 'green', 'days_left' => 45, 'completed_at' => '2026-01-01', 'renewal_from' => '2026-12-01', 'valid_until' => '2026-12-31'],
                ['training_id' => $bravo->id, 'status' => 'yellow', 'days_left' => 14, 'completed_at' => '2025-11-30', 'renewal_from' => '2026-11-01', 'valid_until' => '2026-11-30'],
                ['training_id' => $charlie->id, 'status' => 'red', 'days_left' => null, 'completed_at' => null, 'renewal_from' => null, 'valid_until' => null],
            ],
        ]]);
});

test('a crew member without job titles has no trainings', function () {
    $crewMember = User::factory()->create(['name' => 'No Job Title']);

    Sanctum::actingAs($crewMember);

    $this->getJson(route('api.v1.crew-members.show', $crewMember))
        ->assertOk()
        ->assertExactJson(['data' => [
            'crew_member' => ['id' => $crewMember->id, 'name' => 'No Job Title', 'job_title' => ''],
            'trainings' => [],
            'cells' => [],
        ]]);
});

test('requesting an unknown crew member returns not found', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.crew-members.show', 999))->assertNotFound();
});
