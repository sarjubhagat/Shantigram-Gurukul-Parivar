<?php

namespace App\Models\AccessCenter;

use App\Traits\TracksAudit;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use TracksAudit;
}
