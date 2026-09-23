<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignImage extends Model
{
    protected $fillable = ['design_id', 'url', 'title', 'description', 'angle', 'sort_order'];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }
}
