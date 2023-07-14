Changed files list:
<piearsta folder>/system/config/config.user.php
<piearsta folder>/system/func/functions.func.php
<piearsta folder>/system/app/out/profile/inc/profile.class.php
<piearsta folder>/system/app/out/profile/tmpl/left-side.html

Manidati web site links:

To add the manidati.piearsta.lv resource and sync sessions between the piearsta and manidati
you will need to do the following:

file: <piearsta folder>/system/config/config.user.php
modifications (session sync configuration):

inside the code block -- } elseif (isLOCAL()) {
after the DB connection settings ($config["wkhtmltopdf"] = '/usr/bin/wkhtmltopdf';)
add the following configuration lines (session sync connection parameters):
    // mandatory config params
    $config['sync_remote_host'] = '<ssh remote host to store synced session files>';
    $config['sync_remote_user'] = '<ssh remote login>';
    $config['sync_remote_pass'] = '<ssh remote password>';
    $config['sync_remote_path'] = '<ssh remote path to store synced session files>';
    $config['sync_local_path']  = '<local path, containig the session files>';
    // optional config params
    $config['sync_session_prefix'] = 'sess_'; // if any, session file name will looks like -- sess_<apache session ID>
    $config['sync_session_suffix'] = '';      // if any, session file name will looks like -- sess_<apache session ID>"suffix"

file <piearsta folder>/system/func/functions.func.php
modifications (session sync procedure):
anywhere inside the file add the following procedure:
function sync_session() {
        // return if no session ID
        if(!isset($_SESSION)) return null;
        // set current session ID
        $sess_id = session_id();
        if(!isset($sess_id) || ($sess_id == '')) return null;
        // process only filled session
        if(!isset($_SESSION['user'])) return null;

        // load and check sync config
        $cfg = &loadLibClass('config');
        $check_keys = array('sync_remote_host','sync_remote_user','sync_remote_pass','sync_remote_path','sync_local_path');
        foreach($check_keys as $cfg_key) { $sync_config[$cfg_key] = $cfg->get($cfg_key); }
        $sync_config = array_filter($sync_config);
        foreach($check_keys as $cfg_key) { if(!isset($sync_config[$cfg_key])) return null; }

        // make session file name
        $sync_config['sync_session_prefix'] = $cfg->get('sync_session_prefix');
        $sync_config['sync_session_suffix'] = $cfg->get('sync_session_suffix');

        $sess_file_name = (isset($sync_config['sync_session_prefix'])) ? $sync_config['sync_session_prefix'].$sess_id : $sess_id;
        if(isset($sync_config['sync_session_suffix']) && !empty($sync_config['sync_session_suffix']))
             $sess_file_name = $sess_file_name.$sync_config['sync_session_suffix'];

        if(!empty($sync_config)) {
            // set default ssh port and linux file mode
            $ssh_port = 22; $file_mode = 0777;
            // make local and remote file names
            $local_file = $sync_config['sync_local_path'].'/'.$sess_file_name;
            $remote_file = $sync_config['sync_remote_path'].'/'.$sess_file_name;
            // start sending if session file exists locally
            if(file_exists($local_file)) {
                // ssh connect
                $con = ssh2_connect($sync_config['sync_remote_host'],$ssh_port);
                if(isset($con)) {
                    // ssh auth
                    if(ssh2_auth_password($con,$sync_config['sync_remote_user'],$sync_config['sync_remote_pass'])) {
                        // ssh scp session file to the remote host
                        ssh2_scp_send($con,$local_file,$remote_file,$file_mode);
                        // close remote connection
                        unset($con);
                    }
                }
            }
        }
}

file <piearsta folder>/system/app/out/profile/inc/profile.class.php
modifications (the session sync calls):
1. inside the saveUser() procedure and before procedure return
   (line: if ($this->loginUser(getP('fields/email'), getP('fields/password'))) {),
   add the session sync function call: @sync_session();
2. inside the loginUser() procedure and between the lines:
   -- $this->userData = $_SESSION['user'] = $row;
   and
   -- $this->userData['pc'] = explode('-', $this->userData['person_id']);
   add the session sync function call: @sync_session();


file <piearsta folder>/system/app/out/profile/tmpl/left-side.html
modifications (add manidati href):
inside the <ul class="menu1"> html block and between the {{/foreach}} code line
and </ul> html code add the following html code lines:
                <li class="item">
                    <a href="#" id="manidati_href" onclick="javascript: makeCallURL();"> Mediciniskie izraksti</a>
                </li>
                <script>
                        function makeCallURL() {
                            var x = document.cookie;
                            if(x) { var sess = x.match(/PHPSESSID\=.*\;?/);
                                    console.log(x);
                                    if(sess) {
                                        console.log(sess);
                                        manidati = document.getElementById("manidati_href");
                                        if(manidati) {
                                            window.location.replace('http://andrew.bb-tech.eu/mani_dati.html?'+sess);
                                            /*
                                            manidati.href = 'http://andrew.bb-tech.eu/mani_dati.html?'+sess;
                                            console.log(manidati.href);
                                            return manidati.click();
                                            */
                                        };
                                    };
                            };
                        };
                </script>
----------------------------------------------------------
Thats all :-). Test the whole changes and provide the debug, if necesasary.

Setup ocal envirement:
1) Git clone <piearsta project>

2) Move to /docker folder and set up .env for you based on .env.example

3) Drop you database dump file in /dump folder

4) Run "docker-compose up -d" in CLI

5) Run "docker exec -it piearsta-php bash" in CLI

6) Inside the container run "composer install"

7) Inside the container run "npm install" or "npm ci" in case you need clear cache

8) Inside the container run "npm run build", it should create a critical.css file in css folder and compress images and move them in /build/img folder
