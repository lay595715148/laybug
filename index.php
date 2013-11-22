<?php
$st = date('Y-m-d H:i:s').'.'.floor(microtime()*1000);
include_once __DIR__.'/laybug.php';

Debugger::debug('Debugger, time:'.$st);
?>
