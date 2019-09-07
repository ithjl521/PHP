<?php
use Workerman\Worker;
require_once "./Autoloader.php";

// worker实例1有4个进程，进程id编号将分别为0、1、2、3
$worker1 = new Worker('websocket://0.0.0.0:8585');
// 设置启动4个进程
$worker1->count = 1;
// 每个进程启动后打印当前进程id编号即 $worker1->id
$worker1->onWorkerStart = function($worker1)
{
    echo "worker1->id={$worker1->id}\n";
};

$worker1->onConnect = function($connect)
{
    echo "connect\n";
};


$worker1->onMessage = function($connect,$data)
{
    echo "message\n";
};


$worker1->onClose = function($connect)
{
    echo "Close\n";
};



// 运行worker
Worker::runAll();