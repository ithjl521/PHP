<?php 
use Workerman\Worker;
require_once "./channel/src/Server.php";
require_once "./channel/src/Client.php";
require_once './Autoloader.php';

// 创建一个Worker监听2346端口，使用websocket协议通讯
$ws_worker = new Worker("websocket://0.0.0.0:2000");
$channel_server = new Channel\Server('0.0.0.0', 2206);

// 启动4个进程对外提供服务
$ws_worker->count = 4;
 
$ws_worker->name="test_liao_tian";

/*设置Worker子进程启动时的回调函数，每个子进程启动时都会执行。
注意：onWorkerStart是在子进程启动时运行的，如果开启了多个子进程($worker->count > 1)，
每个子进程运行一次，则总共会运行$worker->count次。*/
$ws_worker->onWorkerStart=function($ws_worker)  // 回调函数的参数就是worker对象
{
    // channel 客户端链接上 服务器
    Channel\Client::connect('192.168.88.15',2206);
    $event_name='私聊';
    // 订阅 worker-<id 事件，并注册事件处理函数
    // $event_data 事件发布(publish)时传递的事件数据。
    Channel\Client::on($event_name,function($event_data)use($ws_worker){
 
        //  print_r($event_data);
        // $ws_worker->connections 存储当前客户端所有连接对象,id为connection的id编号
        //  print_r($ws_worker->connections);
        $to_connect_id=$event_data['to_connection_id'];
        $message=$event_data['content'];
 
 		
        foreach ($ws_worker->connections as $connection) {
 
            if($connection->id==$to_connect_id)
            {
                $connection->send($message);
            }
                 
        }
 
       
    });
 
    // 订阅广播事件
    $event_name = '广播';
    // 收到广播 向所有客户端发送消息
    Channel\Client::on($event_name,function($event_data)use($ws_worker){
        //print_r($event_data);
        $message=$event_data['content'];
        foreach ($ws_worker->connections as $connection) {
            $connection->send($message);
        }
    });
};


// 当客户端连接时，给分配一个随机ID
$ws_worker->onConnect=function($connection){
    $connection->id = md5($connection->id."_".time()."_".rand(10000,99999));
};


$ws_worker->onMessage = function($connection, $data)
{
    $res=array('code'=>200, 'msg'=>'ok', 'data'=>null,'type'=>1);
    // 向客户端发送hello $data
    //print_r($data);
    $data=json_decode($data,true);
    //print_r($data);
    if(!isset($data['type'])||empty($data['type']))// type 1  2
    {
        $res=array('code'=>301, 'msg'=>'消息包格式错误', 'data'=>null);
    }else{
        switch ($data['type']) {
            case '1': // 客户端上线消息
                //print_r($connection->id);
                 
                if(!isset($data['user'])||empty($data['user']))
                {
                    $res=array('code'=>301, 'msg'=>'消息包格式错误', 'data'=>null);
                    break;
                }
                // 维护一个数组 保存 用户 connection_id => user
 
                $dsn='mysql:host=127.0.0.1;dbname=test;';
                $pdo=new PDO($dsn,'root','root');
                //准备SQL语句
                $sql = "INSERT INTO `user`(`connect_id`,`username`) VALUES (:connect_id,:username)";
 
                //调用prepare方法准备查询
                $stmt = $pdo->prepare($sql);
 
                //传递一个数组为预处理查询中的命名参数绑定值，并执行SQL
                $stmt->execute(array(':connect_id' => $connection->id,':username' => $data['user']));
                //获取最后一个插入数据的ID值
                //echo $pdo->lastInsertId() . '<br />';
 
                // 向自己推送一条消息
                $res2['type']=3;// 系统信息
                $res2['data']=array('userinfo' =>$data['user']);// 系统信息
                $connection->send(json_encode($res2));
 
                $msg="用户 ".$data['user']." 上线了~~";
                $res['data']=$msg;
                break;
            case '2': // 客户端群发送消息
                if(!isset($data['user'])||empty($data['user'])||!isset($data['msg'])||empty($data['msg']))
                {
                    $res=array('code'=>301, 'msg'=>'消息包格式错误', 'data'=>null);
                    break;
                }
                $msg="用户 ".$data['user']."说：".$data['msg'];
                $res['data']=$msg;
                break;
            case '3': // 客户端私聊
                if(!isset($data['user'])||empty($data['user'])||!isset($data['msg'])||empty($data['msg'])||!isset($data['friend_id'])||empty($data['friend_id']))
                {
                    $res=array('code'=>301, 'msg'=>'消息包格式错误', 'data'=>null);
                    break;
                }
                $msg="用户 ".$data['user']."对您说：".$data['msg'];
                $res['data']=$msg;
                $res['type']=1;// 聊天消息
                $res1=json_encode($res);
                // 推送给单个用户
                $event_name = '私聊';
                Channel\Client::publish($event_name, array(
                    'content'          => $res1,
                    'to_connection_id' =>$data['friend_id']
                ));
                // 另外还要给自己推条消息
                $msg="您对 ".$data['friendname']."说：".$data['msg'];
                $res['data']=$msg;
                $res['type']=1;// 聊天消息
                $res2=json_encode($res);
                Channel\Client::publish($event_name, array(
                    'content'          => $res2,
                    'to_connection_id' =>$connection->id
                ));
                return;
                break;
             
            default:
                # code...
                break;
        }
    }
    $res['type']=1;// 聊天消息
    $res=json_encode($res);
    // 广播给所有客户端
    $event_name = '广播';
    Channel\Client::publish($event_name, array(
        'content'          => $res
    ));
 
    $dsn='mysql:host=127.0.0.1;dbname=test;';
    $dbh=new PDO($dsn,'root','root');
    $stmt=$dbh->query('SELECT connect_id,username FROM user');
    $row=$stmt->fetchAll();
    $uerHtml="";
    foreach ($row as $key => $value) {
 
        $uerHtml.='<a class="user" onclick="userclick(\''.$value['username'].'\',\''.$value['connect_id'].'\');" value="'.$value['connect_id'].'" href="javascript:void(0);">'.$value['username'].'</a><br/>';
    }
    //print_r($row);
    $res1['type']=2;// 用户消息
    $res1['data']=$uerHtml;
    $res1=json_encode($res1);
     
 
    $event_name = '广播';
    Channel\Client::publish($event_name, array(
        'content'          => $res1
    ));
};


// 关闭链接 将数据库中的该数据删除
$ws_worker->onClose=function($connection)
{
    //echo 3233;
    $dsn='mysql:host=127.0.0.1;dbname=test;';
    $pdo=new PDO($dsn,'root','root');
    $sql="delete from user where connect_id='".$connection->id."'";
    //print_r($sql);
    $pdo->exec($sql);
};


Worker::runAll();





