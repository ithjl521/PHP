<?php
/**
 * Created by PhpStorm.
 * User: 24518
 * Date: 2019/4/27
 * Time: 19:12
 */
namespace App\Models;

class M3Result
{
    public $status;
    public $message;

    public function toJson()
    {
        return json_encode($this,JSON_UNESCAPED_UNICODE);
    }
}