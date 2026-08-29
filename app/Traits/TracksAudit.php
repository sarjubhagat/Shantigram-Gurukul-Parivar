<?php

namespace App\Traits;

trait TracksAudit
{
    protected static function bootTracksAudit(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?? auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\AccessCenter\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\AccessCenter\User::class, 'updated_by');
    }
}
