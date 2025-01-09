<?php

namespace RamiroEstrella\LaravelDatabaseAnonymize\Console\Commands;

use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RamiroEstrella\LaravelDatabaseAnonymize\Services\AnonymizeService;

/**
 * Class AnonymizeCommand
 *
 * This command is responsible for anonymizing database models that use the Anonymizable trait.
 * It anonymizes records in batches, with support for prioritizing and excluding certain models.
 *
 * Command options:
 * - --model: Allows specifying a list of models to be anonymized.
 * - --excludeModel: Allows specifying a list of models to be excluded from anonymization.
 *
 * Methods:
 * - handle(): int
 *   The main command handler. Initializes necessary services, checks environment restrictions, 
 *   and anonymizes models in batches.
 *
 * - processModel(Model $model): void
 *   Anonymizes the records of a specific model in chunks.
 */
final class AnonymizeCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "db:anonymize {--model=*} {--excludeModel=*}";

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = "Anonymize models that use the Anonymizable trait in the codebase.";

    /**
     * Faker generator instance.
     *
     * @var Generator
     */
    protected Generator $faker;

    /**
     * Anonymize service instance.
     *
     * @var AnonymizeService
     */
    protected AnonymizeService $service;

    /**
     * Execute the console command.
     *
     * Initializes the faker and anonymize services, confirms the environment, 
     * and processes anonymizable models based on priority and exclusion options.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->service = new AnonymizeService();

        // Confirm the environment before proceeding with the anonymization.
        if (!$this->confirmToProceed('Environment "' . config('app.env') . '" restricted.', function () {
            return $this->service->isRestricitedEnvironment();
        })) {
            return 0;
        }

        $this->faker = Factory::create();

        // Track the start time of the anonymization process.
        $anonymizationStart = microtime(true);

        // Get all classes that are eligible for anonymization.
        $anonymizableClasses = $this->service->getAnonymizableClasses();

        // Get the models specified for anonymization (if any).
        $allowedModel = $this->option('model');

        // Get the models to be excluded from anonymization (if any).
        $excludedModel = $this->option('excludeModel');

        // Filter the models based on the allowed and excluded lists.
        if ($allowedModel) {
            $anonymizableClasses = array_filter(
                $anonymizableClasses,
                fn($class) => in_array($class, $allowedModel, true)
            );
        }

        $this->warn('Models data anonymization process has started.');

        // Get the list of priority models (if any).
        $anonymizableClassesOrdered = collect(config('database-anonymize.priority_models') ?? []);

        // Exclude the specified models from the anonymization process.
        if ($excludedModel) {
            $anonymizableClassesOrdered = array_filter(
                $anonymizableClasses,
                fn($class) => !in_array($class, $excludedModel, true)
            );

            $anonymizableClasses = array_filter(
                $anonymizableClasses,
                fn($class) => !in_array($class, $excludedModel, true)
            );
        }

        // Ensure models that aren't in the priority list are anonymized last.
        $anonymizableClasses = collect($anonymizableClasses)
            ->diff($anonymizableClassesOrdered)
            ->toArray();

        // Anonymize priority models first (if any).
        if (!empty($anonymizableClassesOrdered)) {
            $this->warn('Anonymizing priority models.');
        }

        foreach ($anonymizableClassesOrdered as $anonymizableClass) {
            $this->processModel(new $anonymizableClass);
        }

        // Anonymize non-priority models next.
        if (!empty($anonymizableClassesOrdered)) {
            $this->warn('Anonymizing non-priority models.');
        }

        $anonymizableClasses = collect($anonymizableClasses)->diff($anonymizableClassesOrdered)->all();

        foreach ($anonymizableClasses as $anonymizableClass) {
            $this->processModel(new $anonymizableClass);
        }

        // Output the total time taken for the anonymization process.
        $this->warn('Anonymization done in ' . CarbonInterval::seconds(microtime(true) - $anonymizationStart)->cascade()->forHumans(['parts' => 3, 'short' => true]));

        return self::SUCCESS;
    }

    /**
     * Anonymizes the records of a specific model in chunks.
     *
     * Validates the model, creates a progress bar, and processes records in chunks, 
     * anonymizing them one by one.
     *
     * @param Model $model The model to be anonymized.
     * @throws \InvalidArgumentException If the model class is invalid.
     * @return void
     */
    protected function processModel(Model $model): void
    {
        $start = microtime(true);

        $this->info('Anonymizing data of ' . $model->getTable() . ' table');

        // Validate that the model class exists and is a valid Eloquent model.
        if (!class_exists($model::class) || !is_subclass_of($model::class, Model::class)) {
            throw new \InvalidArgumentException('Invalid model class: ' . $model::class);
        }

        // Create a progress bar for tracking anonymization progress.
        $progressBar = $this->output->createProgressBar($this->service->recordsCount($model));

        // Customize the progress bar display.
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% | Remaining: %remaining:6s%');

        // Process the model's records in chunks and anonymize each record.
        $this->service->getChunk($model, function (Collection $chunkItems) use ($progressBar) {
            DB::beginTransaction();
            $chunkItems->each(fn(Model $model) => $this->service->anonymize($model));
            DB::commit();
            $progressBar->advance($chunkItems->count());
        });

        // Complete the progress bar.
        $progressBar->finish();

        // Output the time taken for anonymizing the model.
        $this->info(' - Completed in ' . CarbonInterval::seconds(microtime(true) - $start)->cascade()->forHumans(['parts' => 3, 'short' => true]));
    }
}
