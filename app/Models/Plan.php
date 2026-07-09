<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $max_pages
 * @property array|null $features
 * @property bool $is_active
 * @property int $sort
 */
#[Fillable(['name', 'slug', 'max_pages', 'features', 'is_active', 'sort'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'max_pages' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return HasMany<PlanPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
