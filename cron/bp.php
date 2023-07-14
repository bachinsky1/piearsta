#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../system/func/_pid_exec_funcs.php");

$runner = new execScript(getmypid(),20,true);
$runner->setOnlyOneCmd(true);
$runner->checkStats(false);
$runner->setStopSIG(15);
$runner->setMaxExecTime(1);

print_r('Stated root PID: '.getmypid()."\n\n");


while(1) {

    print_r($runner->run('php ./t1.php').' -- ');
    print_r($runner->run('php ./t2.php').' -- ');
    print_r($runner->run('php ./t3.php').' -- ');

//    usleep(500);

//    print_r($runner->run('php ./t1.php').' -- ');
//    print_r($runner->run('php ./t2.php').' -- ');
//    print_r($runner->run('php ./t3.php').' -- ');

}


//    print_r($runner->run('php ./t1.php').' -- ');
//    print_r($runner->run('php ./t1.php').' -- ');
//    print_r($runner->run('php ./t2.php').' -- ');
//    print_r($runner->run('php ./t3.php').' -- ');


exit;


?>