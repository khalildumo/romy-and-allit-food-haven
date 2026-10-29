<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'canteen_name', 'operating_hours', 'contact_info', 'pickup_location'])]
class CanteenProfile extends Model
{
    protected $table = 'canteen_profile';

    public $incrementing = false;

    protected $keyType = 'int';
}
