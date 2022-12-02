<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'model',
        'file_path',
        'file_name',
        'total_rows',
        'processed_rows',
        'completed_at'
    ];

    public $casts = [
        'completed_at' => 'datetime'
    ];

    public function scopeNotCompleted(Builder $query)
    {
        $query->whereNull('completed_at');
    }

    public function scopeForModel(Builder $query, string $model)
    {
        $query->where('model', $model);
    }

    public function percentageComplete(): int
    {
        return floor(($this->processed_rows / $this->total_rows) * 100);
    }
}
