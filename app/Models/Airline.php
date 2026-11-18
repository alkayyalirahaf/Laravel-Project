<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Airline extends Model
{
    protected $fillable = [
        'name',
        'code',
        'logo',
        'country',
        'status',
    ];

    public function airlineAdmins(): HasMany
    {
        return $this->hasMany(AirlineAdmin::class, 'airline_id');
    }

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class, 'airline_id');
    }
}
