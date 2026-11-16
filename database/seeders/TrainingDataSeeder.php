<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds job titles, trainings and users (pilots) from the JSON files in the project root.
 *
 * The ids in the JSON files are only used to resolve the relations between the files;
 * the database assigns its own primary keys.
 */
class TrainingDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $jobTitleIds = $this->seedJobTitles();

            $this->seedTrainings($jobTitleIds);
            $this->seedUsers($jobTitleIds);
        });
    }

    /**
     * Insert the job titles and return a map of JSON id => database id.
     *
     * @return array<int, int>
     */
    private function seedJobTitles(): array
    {
        $jobTitleIds = [];

        /** @var array<int, array{id: int, title: string}> $jobTitles */
        $jobTitles = $this->readJson('job_title.json');

        foreach ($jobTitles as $jobTitle) {
            $jobTitleIds[$jobTitle['id']] = JobTitle::create(['name' => $jobTitle['title']])->id;
        }

        return $jobTitleIds;
    }

    /**
     * Insert the trainings and attach them to their job titles.
     *
     * @param  array<int, int>  $jobTitleIds
     */
    private function seedTrainings(array $jobTitleIds): void
    {
        /** @var array<int, array{id: int, name: string, expirationPeriod: ?int, expiresEndOfMonth: bool, expiresNever: bool, renevalPeriod: int, relatedJobTitle: array<int, int>}> $trainings */
        $trainings = $this->readJson('training.json');

        foreach ($trainings as $trainingData) {
            $training = Training::create([
                'name' => $trainingData['name'],
                'validity_months' => $trainingData['expiresNever'] ? null : $trainingData['expirationPeriod'],
                'renewal_months' => $trainingData['renevalPeriod'],
                'end_of_month_expiry' => $trainingData['expiresEndOfMonth'],
            ]);

            $training->jobTitles()->attach(
                $this->resolveJobTitleIds($trainingData['relatedJobTitle'], $jobTitleIds, "training {$trainingData['id']}")
            );
        }
    }

    /**
     * Insert the pilots as users and attach them to their job titles.
     *
     * @param  array<int, int>  $jobTitleIds
     */
    private function seedUsers(array $jobTitleIds): void
    {
        /** @var array<int, array{id: int, name: string, lastname: string, birthdate: string, mail: string, company: string, jobTitle: array<int, int>}> $pilots */
        $pilots = $this->readJson('pilot.json');

        foreach ($pilots as $pilot) {
            $user = User::create([
                'name' => "{$pilot['name']} {$pilot['lastname']}",
                'email' => $pilot['mail'],
                'password' => 'password',
            ]);

            $user->jobTitles()->attach(
                $this->resolveJobTitleIds($pilot['jobTitle'], $jobTitleIds, "pilot {$pilot['id']}")
            );
        }
    }

    /**
     * Translate JSON job title ids into database ids.
     *
     * @param  array<int, int>  $jsonIds
     * @param  array<int, int>  $jobTitleIds
     * @return array<int, int>
     */
    private function resolveJobTitleIds(array $jsonIds, array $jobTitleIds, string $source): array
    {
        return array_map(function (int $jsonId) use ($jobTitleIds, $source): int {
            if (! isset($jobTitleIds[$jsonId])) {
                throw new RuntimeException("Unknown job title id {$jsonId} referenced by {$source}.");
            }

            return $jobTitleIds[$jsonId];
        }, $jsonIds);
    }

    /**
     * Read and decode a JSON file from the project root.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $fileName): array
    {
        return json_decode(file_get_contents(base_path($fileName)), true, flags: JSON_THROW_ON_ERROR);
    }
}
