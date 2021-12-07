<?php

namespace Modules\Item\Models;

use App\Models\Tenant\Item;
use App\Models\Tenant\ModelTenant;

class Size extends ModelTenant
{
    public $timestamps = false;
    protected $fillable = [
        'name',
    ];

    public function items()
    {
        return $this->hasMany(Size::class);
    }

}
