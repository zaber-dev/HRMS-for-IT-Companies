<?php

namespace App\Models;

use Database\Factories\SkillCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class SkillCategory extends Model
{
    /** @use HasFactory<SkillCategoryFactory> */
    use HasFactory;

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
