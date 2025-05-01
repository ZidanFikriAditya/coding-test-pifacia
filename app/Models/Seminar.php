<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Seminar extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'title',
        'description',
        'schedule',
        'is_active',
        'additional_info',
        'user_id',
    ];

    protected $keyType = 'string';
    public $incrementing = false; 

    protected $casts = [
        'schedule' => 'datetime',
        'additional_info' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = \Illuminate\Support\Str::uuid();
        });
    }

    public static function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'required|date',
            'is_active' => 'boolean',
            'additional_info' => 'nullable|array',
            'additional_info.*' => 'string|max:255',
        ];
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
