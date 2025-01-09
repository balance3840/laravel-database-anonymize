<?php

namespace RamiroEstrella\LaravelDatabaseAnonymize\Traits;

use Faker\Generator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use LogicException;

trait Anonymizable
{
    public function anonymizeCondition(): Builder
    {
        return static::hasMacro('withTrashed') ? static::withTrashed() : static::query();
    }
    
    public function toAnonymize(Generator $faker)
    {
        throw new LogicException('Please implement the anonymizable method on your model.');
    }
}
