<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Payment extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'participant_id',
        'file_path',
        'uploaded_at',
        'is_verified',
        'metadata',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public static function rules()
    {
        return [
            'participant_id' => 'required|uuid|exists:participants,id',
            'file_path' => 'nullable|file|mimes:pdf|between:100,500',
            'uploaded_at' => 'nullable|date',
            'metadata' => 'nullable|array',
            'metadata.*' => 'string|max:255',
        ];
    }
    
    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = \Illuminate\Support\Str::uuid();
        });
    }
}
