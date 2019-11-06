<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;


class Member extends Model
{
    protected $table = 'member';
    protected $id = 'id';
    public $timestamps = false;
}
