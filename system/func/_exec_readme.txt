Usage:
At the top of the service script this line - require_once(dirname(__FILE__) . "/../system/func/_pid_exec_funcs.php");
Call class like this - $runner = new execScript(<master process id>,<number of child processes>,<use memorty or tmp file to control child stack>);
Init runner example: $runner = new execScript(getmypid(),3,false); ("false" - means use tmp file to control child stack)
Run the child process within - $runner->run('php <script file>'); (returns child process id if start was success, null - otherwise)
Stop all childs example - $runner->stopAll(); (the same, as php "exit" call)
When php "exit" will called, all child processes will be stopped automatically.