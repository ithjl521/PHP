<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';
    protected $id = 'id';
    public $timestamps = false;
}
