<?php

namespace RamiroEstrella\LaravelDatabaseAnonymize\Services;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RamiroEstrella\LaravelDatabaseAnonymize\Traits\Anonymizable;
use ReflectionClass;

/**
 * Class AnonymizeService
 *
 * Service responsible for managing the anonymization of models.
 *
 * Methods:
 * - __construct(): void
 *   Initializes the faker generator instance.
 *
 * - isRestricitedEnvironment(): bool
 *   Checks if the current environment is restricted for anonymization.
 *
 * - getAnonymizableClasses(): array<int, string>
 *   Retrieves all classes in the codebase that use the Anonymizable trait.
 *
 * - getFullyQualifiedClassNameFromFile(string $filePath): ?string
 *   Extracts the fully qualified class name from a given file path.
 *
 * - classUsesTrait(ReflectionClass $class, string $trait): bool
 *   Checks if a given class uses a specific trait.
 *
 * - recordsCount(Model $model): int
 *   Returns the number of records for a given model.
 *
 * - getChunk(Model $model, callable $callback): bool
 *   Processes a model's records in chunks, invoking a callback for each chunk.
 *
 * - anonymize(Model $model): bool
 *   Anonymizes the data of a given model.
 */
class AnonymizeService
{
    /**
     * Faker generator instance.
     *
     * @var Generator
     */
    protected Generator $faker;

    protected const RELATIONS_KEY = 'relations';

    /**
     * Constructor.
     *
     * Initializes the Faker generator instance.
     */
    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /**
     * Checks if the current environment is restricted for anonymization.
     *
     * @return bool
     */
    public function isRestricitedEnvironment(): bool
    {
        return in_array(config('app.env'), config('database-anonymize.restricted_env', []));
    }

    /**
     * Retrieves all classes in the codebase that use the Anonymizable trait.
     *
     * @return array<int, string>
     */
    public function getAnonymizableClasses(): array
    {
        $classes = [];

        $files = File::allFiles(app_path());

        foreach ($files as $file) {
            $className = $this->getFullyQualifiedClassNameFromFile($file->getPathname());

            if ($className && class_exists($className)) {
                $reflectionClass = new ReflectionClass($className);
                if ($this->classUsesTrait($reflectionClass, Anonymizable::class)) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }

    /**
     * Extracts the fully qualified class name from a given file path.
     *
     * @param string $filePath
     * @return string|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getFullyQualifiedClassNameFromFile(string $filePath): ?string
    {
        $contents = File::get($filePath);

        // Use regex to extract the namespace and class name
        if (preg_match('/namespace\s+(.+?);/', $contents, $matches)) {
            $namespace = $matches[1];
        } else {
            $namespace = null;
        }

        if (preg_match('/class\s+([a-zA-Z0-9_]+)/', $contents, $matches)) {
            $class = $matches[1];
        } else {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * Checks if a given class uses a specific trait.
     *
     * @param ReflectionClass $class
     * @param string $trait
     * @return bool
     */
    private function classUsesTrait(ReflectionClass $class, string $trait): bool
    {
        return in_array($trait, $class->getTraitNames());
    }

    protected function getQuery(Model $model): Builder
    {
        return $model->anonymizeCondition();
    }

    /**
     * Returns the number of records for a given model.
     *
     * @param Model $model
     * @return int
     */
    public function recordsCount(Model $model): int
    {
        return $model::query()->count();
    }

    /**
     * Processes a model's records in chunks, invoking a callback for each chunk.
     *
     * @param Model $model
     * @param callable $callback
     * @return bool
     */
    public function getChunk(Model $model, callable $callback): bool
    {
        return $this->getQuery($model)->chunkById(
            config('database-anonymize.chunk_size', 1000),
            $callback
        );
    }

    /**
     * Anonymizes the data of a given model.
     *
     * Updates the model's anonymizable fields with fake data.
     *
     * @param Model $model
     * @return bool
     */
    public function anonymize(Model $model): bool
    {
        $anonymizeData = $model->toAnonymize($this->faker);

        $relations = isset($anonymizeData[self::RELATIONS_KEY]) ? $anonymizeData[self::RELATIONS_KEY] : [];

        if ($relations) {
            foreach ($relations as $relation => $data) {
                $model->$relation()->update($data);
            }
            Arr::forget($anonymizeData, self::RELATIONS_KEY);
        }

        $model
            ->setTouchedRelations([])
            ->updateQuietly(
                $anonymizeData
            );

        return true;
    }
}
