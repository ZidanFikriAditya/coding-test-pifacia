<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadExport extends Model
{
    protected $fillable = [
        'name',
        'type',
        'path',
        'status',
        'error',
        'user_id',
        'finished_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
