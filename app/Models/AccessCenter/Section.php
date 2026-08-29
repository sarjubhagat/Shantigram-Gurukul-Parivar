<?php

namespace App\Models\AccessCenter;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use SoftDeletes, TracksAudit;

    protected $fillable = ['section_name', 'icon', 'order'];

    public function modules()
    {
        return $this->hasMany(Module::class);
    }
}
