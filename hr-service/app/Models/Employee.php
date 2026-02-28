<?php

namespace App\Models;

use App\Enums\CountryEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @author Ridwan Kasim
 * table name: employees
 *
 * @property integer $id
 * @property string $name
 * @property string $last_name
 * @property float $salary
 * @property string $country
 * @property string|null $ssn
 * @property string|null $address
 * @property string|null $goal
 * @property string|null $tax_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 */

class Employee extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'last_name',
        'salary',
        'country',
        'ssn',
        'address',
        'goal',
        'tax_id',
    ];

    /**
     * Get country-specific fields.
     */
    public static function countryFields(string $country): array
    {
        return match ($country) {
            CountryEnum::COUNTRY_USA->value => ['ssn', 'address'],
            CountryEnum::COUNTRY_GERMANY->value => ['goal', 'tax_id'],
            default => [],
        };
    }

    /**
     * Get the fields that changed (excluding timestamps).
     */
    public function getChangedFields(): array
    {
        $changes = $this->getChanges();
        unset($changes['updated_at'], $changes['created_at']);

        return array_keys($changes);
    }
}
