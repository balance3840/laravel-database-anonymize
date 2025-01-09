
# Laravel Database Anonymize

**Laravel Database Anonymize** is a package designed to streamline data anonymization, enabling organizations to safeguard privacy, comply with regulations, reduce the risk of data breaches, and share data securely. 

### Key Benefits:

1. **Privacy Protection**  
   Anonymization removes or masks sensitive personally identifiable information (PII) such as names, addresses, emails, and phone numbers, preserving individuals' privacy.

2. **Regulatory Compliance**  
   Meet legal and industry standards like the EU's General Data Protection Regulation (GDPR) by anonymizing sensitive data to ensure compliance.

3. **Risk Mitigation**  
   Minimize the impact of data breaches by reducing the exposure of sensitive information, thereby safeguarding against financial losses, reputational damage, and identity theft.

4. **Secure Data Sharing**  
   Share anonymized datasets with researchers or other organizations without compromising privacy, fostering collaboration and innovation in fields like healthcare, finance, and social sciences.

---

## Installation

Install the package via Composer:

```bash
composer require ramiroestrella/laravel-database-anonymize
```

Publish the configuration file with:

```bash
php artisan vendor:publish --provider="RamiroEstrella\LaravelDatabaseAnonymize\ServiceProvider"
```

The published configuration file (`config/laravel-database-anonymize.php`) includes:

```php
return [
    'locale' => 'en_US',
    'chunk_size' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Restricted Environments
    |--------------------------------------------------------------------------
    | Define environments (e.g., production, staging) that require confirmation 
    | before running anonymization commands for added security.
    */
    'restricted_env' => ['production', 'staging'],

    /*
    |--------------------------------------------------------------------------
    | Model Ordering
    |--------------------------------------------------------------------------
    | Optionally define the order of model anonymization. Priority models are
    | processed first to ensure dependencies are handled correctly.
    */
    'priority_models' => [],
];
```

---

## Usage

### Step 1: Implement the `Anonymizable` Trait

Add the `Anonymizable` trait to models containing sensitive data and define the `anonymizableAttributes` method.

Example (`App\Models\User`):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use RamiroEstrella\LaravelDatabaseAnonymize\Traits\Anonymizable;
use Faker\Generator;

class User extends Authenticatable
{
    use Anonymizable;

    public function anonymizableAttributes(Generator $faker): array
    {
        return [
            'email' => $this->id . '@custom.dev',
            'password' => 'secret',
            'firstname' => $faker->firstName,
            'surname' => $faker->lastName,
            'phone' => $faker->e164PhoneNumber,
            'position' => $faker->jobTitle,
            'token' => null,
        ];
    }

    // Optional: Specify conditions for anonymization
    public function anonymizableCondition(): Builder
    {
        return self::withTrashed()->where('something', '>=', '...');
    }
}
```

### Step 2: Anonymize Related Models

If your model has relationships, you can specify the data to anonymize within related models using the `relations` property.

Example (`App\Models\Employee`):
```php
<?php

namespace App\Models;

use RamiroEstrella\LaravelDatabaseAnonymize\Traits\Anonymizable;
use Faker\Generator;

class Employee extends Authenticatable
{
    use Anonymizable;

    public function anonymizableAttributes(Generator $faker): array
    {
        $firstName = $faker->firstName();
        $lastName = $faker->lastName();

        return [
            'name' => $firstName . ' ' . $lastName,
            'relations' => [
                'person' => [
                    'first_name' => $firstName,
                    'family_name' => $lastName,
                ],
            ],
        ];
    }
}
```

In this example:
- The `name` attribute of the `Employee` model is anonymized.  
- The related `person` model has its `first_name` and `family_name` attributes anonymized as well.

### Step 3: Run the Anonymization Command

Anonymize all models:
```bash
php artisan db:anonymize
```

Anonymize specific models:
```bash
php artisan db:anonymize --model=App\\Models\\User --model=App\\Models\\Employee
```

Exclude a specific model:
```bash
php artisan db:anonymize --exclude=App\\Models\\Employee
```

---

## Configuration Options

- **Locale**: Defines the locale used by Faker (default: `en_US`).  
- **Chunk Size**: Processes data in chunks for memory efficiency (default: `1000`).  
- **Restricted Environments**: Prevents accidental anonymization in critical environments like production or staging.  
- **Priority Models**: Ensures dependent models are anonymized in the correct order.

---

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
