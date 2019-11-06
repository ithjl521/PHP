<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;


class Category extends Model
{
    protected $table = 'category';
    protected $id = 'id';
    public $timestamps = false;
}