<?php
ini_set('display_errors', false);
require_once('db_conf.inc.php');

try{
    // Connect database
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    result_false(mysqli_connect_errno());

    // Get arguments
    $arg_email = '';

    result_false(count($argv) != 3);
    $arg_email = $argv[1].'@'.$argv[2];

    // check for existing user
    $dbmail = $db->real_escape_string($arg_email);
    $result = $db->query("SELECT jid, password FROM xmpp_user WHERE jid LIKE '".$dbmail."' AND active='y' AND server_id='".$isp_server_id."'");
    result_false($result->num_rows != 1);
    result_true();

}catch(Exception $ex){
    echo 0;
    exit();
}

function result_false($cond = true){
    if(!$cond) return;
    echo 0;
    exit();
}
function result_true(){
    echo 1;
    exit();
}

?>