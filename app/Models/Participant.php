<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Participant extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'seminar_id',
        'name',
        'email',
        'registered_at',
        'extra_data',
        'is_confirmed',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'extra_data' => 'array',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public static function rules()
    {
        return [
            'seminar_id' => 'required|uuid|exists:seminars,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'registered_at' => 'nullable|date',
            'extra_data' => 'nullable|array',
            'extra_data.*' => 'string|max:255',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = \Illuminate\Support\Str::uuid();
        });
    }

    public function seminar()
    {
        return $this->belongsTo(Seminar::class);
    }
}
