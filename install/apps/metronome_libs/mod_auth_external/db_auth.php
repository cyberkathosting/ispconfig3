<?php
ini_set('display_errors', false);
require_once('db_conf.inc.php');

try{
    // Connect database
    $db = new mysqli($db_host, $db_user, $db_pass, $db_name);
    result_false(mysqli_connect_errno());

    // Get arguments
    $arg_email = '';
    $arg_password = '';

    result_false(count($argv) != 4);
    $arg_email = $argv[1].'@'.$argv[2];
    $arg_password = $argv[3];

    // check for existing user
    $dbmail = $db->real_escape_string($arg_email);
    $result = $db->query("SELECT jid, password FROM xmpp_user WHERE jid LIKE '".$dbmail."' AND active='y' AND server_id='".$isp_server_id."'");
    result_false($result->num_rows != 1);

    $user = $result->fetch_object();

    // check for domain autologin api key
    $domain_key = 'f47kmm5Yh5hJzSws2KTS';

    checkAuth($argv[1], $argv[2], $arg_password, $user->password, $domain_key);
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
function checkAuth($user, $domain, $pw_arg, $pw_db, $domain_key){
    if(crypt($pw_arg, $pw_db) == $pw_db)
        result_true();

    if($domain_key){
        $datetime = new DateTime();
        $datetime->setTimezone(new DateTimeZone("UTC"));
        for($t = $datetime->getTimestamp(); $t >= $datetime->getTimestamp()-30; $t--){
            $pw_api = md5($domain.'@'.$domain_key.'@'.$user.'@'.$t);
            if($pw_api == $pw_arg)
                result_true();
        }
    }
    result_false();
}
?>