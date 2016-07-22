<?php
    
    
    // prevent non AJAX call.
    if(!$_SERVER['HTTP_X_REQUESTED_WITH'])
    {
        header("HTTP/1.0 403 Forbidden");
        exit;
    }
    
    
    // lagency: google ips management and validating ,
    // lagency: t*e*m*p solution (furing require dynamic checking on serversite with strategies)
    
    include_once ('header_config.php');
    include_once ('header_crops.php');
    include_once ('hitcounter/counter_config.php');
    include_once ('simple_html_dom.php');
    
    
    function connect_dblink() {
        
        // ################################################
        // ######### connect + select  database ###########
        // ################################################
        
        global $localhost, $dbuser, $dbpass, $dbname;
        
        $link = mysql_connect($localhost, $dbuser, $dbpass);
        
        if (!$link) {
            die('Could not connect: ' . mysql_error());  // remove ?
        }
        
        $dbselect = mysql_select_db($dbname, $link);
        if (!$dbselect) {
            die("Can't use database $dbname! : " . mysql_error()); // remove ?
        }
        
        
        return $link;

    }
    
    function close_dblink($link) {
        mysql_close($link);
    }
    
    
    function count_lastday($link) {
        
        $ip= $_SERVER["REMOTE_ADDR"];
        date_default_timezone_set("Asia/Hong_Kong");
        $mod_offset = '-' . '1' . ' day';
        $datetime_mod = date("Y/m/d H:i:s", strtotime($mod_offset));
        
        $counts = mysql_query("SELECT COUNT(id) FROM info_lastday WHERE ip_address='$ip' AND datetime>'$datetime_mod'", $link);
        list($number) = mysql_fetch_row($counts);
        
        return $number;
    }
    

    function add_lastday($link) {
        
        $ip= $_SERVER["REMOTE_ADDR"];
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime =date("Y/m/d") . ' ' . date('H:i:s') ;
        
        $adddata = mysql_query("INSERT INTO info_lastday(ip_address, datetime) VALUES('$ip' , '$datetime')", $link);

    }
    
    
    
    
    function fetchdata($link, $word, &$cache_status)
    {
        
        
        
        // ####################################################
        // ######### query and update words ###########
        // ####################################################
        
        
        
        // gather user data
        $ip= $_SERVER["REMOTE_ADDR"];
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime =date("Y/m/d") . ' ' . date('H:i:s') ;
        
        
        $fetched = '';
        $slashed_word= addslashes($word);
        
        if (strlen($slashed_word) > 190) {return $fetched;}
        
        
        // query and update words
        $query_str = "SELECT id FROM words WHERE word='$slashed_word' LIMIT 1";
        $result = mysql_query($query_str, $link);
        $row = mysql_fetch_array( $result );
        if ($row) {
            
            $wordid = $row['id'];
            
            $result = mysql_query("SELECT data, status FROM words WHERE id=$wordid", $link);
            $row = mysql_fetch_array( $result );
            
            if (strlen($row['data'])>100) {
                $fetched = $row['data'];
                
                $updatedata = mysql_query("UPDATE words SET ip_address='$ip', loaded=loaded+1, lasttime='$datetime' WHERE id=$wordid ", $link) ;
            }
            else {
                if ($row['status'] === 'empty') {
                    $cache_status = 'empty';
                    $fetched = $row['data'];
                    $updatedata = mysql_query("UPDATE words SET ip_address='$ip', loaded=loaded+1, lasttime='$datetime' WHERE id=$wordid ", $link) ;
                }
            }
        }
        else {
            $adddata = mysql_query("INSERT INTO words(ip_address, word, count, loaded, addtime, lasttime) VALUES('$ip', '$slashed_word', 0, 0, '$datetime', '$datetime')", $link);
        }
        

        return $fetched;
        
    }
    
    
    
    function handler_result_sort_lasttime($a, $b) {
        $a_time = $a['lasttime'];
        $b_time = $b['lasttime'];
        
        if ($a_time == $b_time) {
            return 0;
        }
        return ($a_time < $b_time) ? -1 : 1;
    }
    
    
    
    function precheck_json($str) {
        
        if (json_encode($str) || !$str) {
            return $str;
        }
        
        $len = strlen($str);
        $start = '';
        $end = '';
        
        for ($i=$len; $i>0; $i--) {
            $part = substr($str, 0, $i);
            if (json_encode($part)) {
                $start = $part;
                break;
            }
        }
        
        for ($i=0; $i<$len; $i++) {
            $part = substr($str, $i);
            if (json_encode($part)) {
                $end = $part;
                break;
            }
        }
        return $start.' '.$end;
    }

    
    
    function get_search_handler($link, $type = 'normal') {
        
        
        // current time
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
        
        // $mod_offset = '-' . '6' . ' minute';
        $mod_offset = '-' . '1' . ' minute';
        $datetime_mod = date("Y/m/d H:i:s", strtotime($mod_offset));
        
        $mod_offset_proxy = '-' . '1' . ' minute';
        $datetime_mod_proxy = date("Y/m/d H:i:s", strtotime($mod_offset_proxy));
        
        
        $results = array();
        
        
        
        
        // backup type
        if ($type === 'backup') {
            
            $search_handler = array(
                                    "handler" => "",
                                    "handler_id" => "",
                                    "mirror_prefix" => "",
                                    "proxy_address" => "",
                                    "proxy_port" => "",
                                    "proxy_type" => "",
                                    "backup_prefix" => "",
                                    "lasttime" => "",
                                    );
            
            
            // $result_backup = mysql_query("SELECT * FROM words_backup ORDER BY lasttime LIMIT 1", $link);
            /* temp */
            $result_backup = mysql_query("SELECT * FROM words_backup WHERE id=1 ORDER BY lasttime LIMIT 1", $link);
            $row_backup = mysql_fetch_array($result_backup);
            
            $search_handler["handler"] = 'backup';
            $search_handler["handler_id"] = $row_backup['id'];
            $search_handler["backup_prefix"] = $row_backup['name'];
            $search_handler["lasttime"] = $row_backup['lasttime'];
            $id = $row_backup['id'];
            
            $adddata = mysql_query("UPDATE words_backup SET lasttime='$datetime' , total=total+1, total_day=total_day+1 WHERE id=$id", $link);
            
            $results[] = $search_handler;
            return $results;
            
        }
        
        
        
        // query str
        $result_mirror_query_str = "SELECT * FROM words_href WHERE lasttime<'$datetime_mod' ORDER BY lasttime";
        $result_proxy_query_str =  "SELECT * FROM words_proxy WHERE lasttime<'$datetime_mod_proxy' ORDER BY lasttime";
        if ($type === 'active') {
            $result_mirror_query_str = "SELECT * FROM words_href WHERE lasttime<'$datetime_mod' AND sick=0 ORDER BY lasttime";
            $result_proxy_query_str =  "SELECT * FROM words_proxy WHERE lasttime<'$datetime_mod_proxy' AND sick=0 ORDER BY lasttime";
        }
        
        if ($type === 'sick') {
            $result_mirror_query_str = "SELECT * FROM words_href WHERE lasttime<'$datetime_mod' AND sick>0 ORDER BY lasttime";
            $result_proxy_query_str =  "SELECT * FROM words_proxy WHERE lasttime<'$datetime_mod_proxy' AND sick>0 ORDER BY lasttime";
        }
        
        
        
        // perform query
        $result_mirror = mysql_query($result_mirror_query_str, $link);
        while ($row_mirror = mysql_fetch_array($result_mirror)) {
            
            $search_handler = array(
                                    "handler" => 'mirror',
                                    "handler_id" => $row_mirror['id'],
                                    "mirror_prefix" => $row_mirror['prefix'],
                                    "proxy_address" => '',
                                    "proxy_port" => '',
                                    "proxy_type" => '',
                                    "backup_prefix" => '',
                                    "lasttime" => $row_mirror['lasttime'],
                                    );
            
            $results[] = $search_handler;
        }
        
        
        $result_proxy = mysql_query($result_proxy_query_str, $link);
        while ($row_proxy = mysql_fetch_array($result_proxy)) {
            
            $search_handler = array(
                                    "handler" => 'proxy',
                                    "handler_id" => $row_proxy['id'],
                                    "mirror_prefix" => '',
                                    "proxy_address" => $row_proxy['address'],
                                    "proxy_port" => $row_proxy['port'],
                                    "proxy_type" => $row_proxy['type'],
                                    "backup_prefix" => '',
                                    "lasttime" => $row_proxy['lasttime'],
                                    );
            
            $results[] = $search_handler;
        }
        
        
        
        
        // sort by lasttime
        usort($results, "handler_result_sort_lasttime");
        
        
        
        // update lasttime after fetch
        if ($results){
            $first = $results[0];
            $id = $first['handler_id'];
            
            if ($first['handler'] === 'mirror') {
                $adddata = mysql_query("UPDATE words_href SET lasttime='$datetime' , total=total+1, total_day=total_day+1 WHERE id=$id", $link);
                
            }
            
            if ($first['handler'] === 'proxy') {
                $adddata = mysql_query("UPDATE words_proxy SET lasttime='$datetime' , total=total+1, total_day=total_day+1 WHERE id=$id", $link);
                
            }
        }
    
        
        
        return $results;

    }
    
    
    
    function update_search_handler($link, $search_handler, $status, $log) {

        $id = $search_handler['handler_id'];
        $handler_table = "";
        if ($search_handler['handler'] === 'mirror') {
            $handler_table = 'words_href';
        }
        
        if ($search_handler['handler'] === 'proxy' ) {
            $handler_table = 'words_proxy';
        }
        
        if ($search_handler['handler'] === 'backup') {
            $handler_table = 'words_backup';
        }
        
        if (!$handler_table) {
            $handler_table = 'words_backup';
            $status = "error";
            $log = "escaped search_handler" . $log;
            
        }
        
        
        
        // previous status
        $result = mysql_query("SELECT * FROM $handler_table WHERE id=$id", $link);
        $row = mysql_fetch_array($result);
        $previous_sick = intval($row['sick']);
        $mod_sick = $previous_sick + 1;
        $previous_log = $row['log'];
        

        // current time
        $ip= $_SERVER["REMOTE_ADDR"];
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
        $mod_offset = '+' . $mod_sick . ' hour';
        $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
        
        
        
        $new_log = $log . ' - ' . $ip . ' ' . $datetime . ';  ' . $previous_log;
        
        
        switch ($status) {

            case "error":
                // non robot status, shorten waiting intermite
                if($mod_sick < 10) {
                    $mod_offset = '+' . $mod_sick * 20 . ' minute';
                    
                    // if timeout issue, shorten waiting intermite
                    if (strpos($log, 'bytes received') !== false) {
                        $mod_offset = '+' . $mod_sick * 5 . ' minute';
                    }
                    // if resolving issue, shorten waiting itermitte
                    else if (strpos($log, 'Resolving timed out after') !== false) {
                        $mod_offset = '+' . $mod_sick * 5 . ' minute';
                    }
                    
                    $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                }
                
                
                if($search_handler['handler'] === 'proxy') {
                    $mod_offset = '+' . $mod_sick * 30 . ' minute';
                    $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                }
                
                
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, error=error+1, error_day=error_day+1, log='$new_log' WHERE id=$id", $link);
                break;
        
            case "robot":
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, robot=robot+1, robot_day=robot_day+1, log='$new_log'  WHERE id=$id", $link);
                break;
        
            case "unknown":
                // non robot status, shorten waiting intermite
                if($mod_sick < 10) {
                    $mod_offset = '+' . $mod_sick * 10 . ' minute';
                    $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                }
                
                if($search_handler['handler'] === 'proxy') {
                    $mod_offset = '+' . $mod_sick * 30 . ' minute';
                    $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                }
                
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, unknown=unknown+1, unknown_day=unknown_day+1, log='$new_log'  WHERE id=$id", $link);
                break;
        
            case "empty":
                if ($search_handler['handler'] === 'backup') {
                    $adddata = mysql_query("UPDATE $handler_table SET sick=$mod_sick, empty=empty+1, empty_day=empty_day+1, log='$new_log'  WHERE id=$id", $link);
                }
                else {
                    $adddata = mysql_query("UPDATE $handler_table SET empty=empty+1, empty_day=empty_day+1, log='$new_log'  WHERE id=$id", $link);
                }
                break;
        
            case "succeed":
                $adddata = mysql_query("UPDATE $handler_table SET sick=0, succeed=succeed+1, succeed_day=succeed_day+1 WHERE id=$id", $link);
                break;
                
            default:
                $new_log = 'escaped error; ' . $new_log;
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, error=error+1, error_day=error_day+1, log='$new_log' WHERE id=$id", $link);
                
        }
        
        
    }
    
    
    
    function ping($host, $port = 80, $timeout = 1) {
        $fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fsock) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    // simple strmask for encrypt
    function strmask($s) {
        $o = '';
        for ($i = 0; $i < strlen($s) - 1; $i+=2) {
            $o = $o . $s[$i + 1] . $s[$i];
        }
        if (strlen($s) % 2 !== 0) {
            $o = $o . $s[strlen($s) - 1];
        }
        return $o;
    }
    
    
    // get url name
    function get_url_name($url) {
        return reset(explode("/", end(explode("://", $url, 2)), 2));
    }
    
    
    // get url base
    function get_url_base($url) {
        return reset(explode("://", $url, 2)) . '://' . reset(explode("/", end(explode("://", $url, 2)), 2));
    }
    
    
    
    // prefetch cookie
    function prefetch_cookie($url) {
        
        $cookie_str = "";
        $ch = curl_init();
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // set cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            return $cookie_str;
        }
        
        curl_close($ch);
        
        $pattern = '/timeHash=.*?domain.*?;/';
        $res = preg_match($pattern, $output, $matches);
        
        if (!$matches) {
            return $cookie_str;
        }
        else {
            $cookie_str = $matches[0];
        }
        
        return $cookie_str;
    }

    
    
    
    
    function fetch_backup($link, $search_handler, $q, $s, &$status, &$log) {
        
        
        $host_name = get_url_name($search_handler['backup_prefix']);

        $str = '';
        
        // parse weibo
        if ($host_name === 'vdisk.weibo.com') {
            $str = $q;
        }
        
        if ($host_name === '149.174.97.243' || $host_name === 'www.wow.com') {
            $str = $s;
        }
        
        
        
        $fetched = array();
        $url = $search_handler['backup_prefix'] . $str;
        
        
        // create curl resource
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 2);
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        
        $header = 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8; User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_5) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11';
        
        curl_setopt($ch, CURLOPT_HEADER, $header);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        
        
        
        
        // $output contains the output string
        $output = curl_exec($ch);
        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
        
        
        curl_close($ch);
        
        
        
        
        
        // parse weibo
        if ($host_name === 'vdisk.weibo.com') {
            $fetched = parse_weibo($output, $status, $log);
        }
        
        
        // parse aol && wow
        if ($host_name === '149.174.97.243' || $host_name === 'www.wow.com') {
            $fetched = parse_aol($output, $status, $log);
        }
        
        
        return $fetched;
        
        
    }
    

    
    
    
    
    
    function parse_weibo($output, &$status, &$log) {
        
        
        $fetched = array();
        
        $html = str_get_html($output);
        
        $sort_main = $html->find('.v_sort_main', 0);
        
        
        // sort_main not found
        if (!$sort_main) {
            $status = 'unknown';
            $log = 'sort_main not found';
            
            // whole page html stored, sus temp
            $output = $output . '<br><span id="search_keyword">' . $str . '</span></br>';
            $output = $output . '<br><span id="search_url">' . $url . '</span></br>';
            $output = addslashes($output);
            $adddata = mysql_query("UPDATE words_backup SET data='$output'", $link);
            
            return $fetched;
        }
        
        
        
        $sort_main_first = $sort_main->children(0);
        $sort_main_second = $sort_main->children(1);
        
        // sort_main_first not found
        if (!$sort_main_first) {
            $status = 'unknown';
            $log = 'sort_main_first not found';
            return $fetched;
        }
        
        
        
        // empty
        if ($sort_main_first->getAttribute('class') === 'vd_allnofind') {
            $status = 'empty';
            $log = 'backup result empty';
            return $fetched;
        }
        
        
        // sort_main_second not found
        if (!$sort_main_second) {
            $status = 'unknown';
            $log = 'sort_main_second not found';
            return $fetched;
        }
        
        
        // result table not found
        if ($sort_main_second->getAttribute('id') !== 'search_table') {
            $status = 'unknown';
            $log = 'result table not found';
            return $fetched;
        }
        
        
        
        // start parse result
        $ret = $sort_main_second->find('.sort_name_intro');
        
        
        if (count($ret) === 0) {
            $status = 'unknown';
            $log = 'result table is empty';
            return $fetched;
        }
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value->find('a', 0);
            
            $href = $l->href;
            
            $title = $l->plaintext;
            
            $s = $value->find('.sort_name_time', 0);
            $des = $s->plaintext;
            
            // if not valid values, skip
            if (! ($href && $title && $des)) {
                continue;
            }
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        if (count($fetched) === 0) {
            $status = 'unknown';
            $log = 'escaped sus, no valid values left. original ret:' . count($ret);
            return $fetched;
        }
        
        
        $status = 'succeed';
        $log = 'result succeed';
        
        return $fetched;
    }
    
    
    
    
    function parse_aol($output, &$status, &$log) {
        
        
        
        $fetched = array();
        
        
        
        $html = str_get_html($output);
        
        
        // find target div.algo
        $div_algo = $html->find('div.ALGO', -1);
        if ($div_algo) {
            if ($div_algo->getAttribute('class') !== 'ALGO') {
                $div_algo = '';
            }
        }
        
        
        // find NH
        $div_NH = $html->find('div.NH', 0);
        
        
        if ((!$div_algo) && $div_NH) {
            $status = 'empty';
            $log = 'empty';
            return $fetched;
        }
        
        
        if ((!$div_algo) && (!$div_NH)) {
            $status = 'unknown';
            $log = 'div_algo div_NH neither found';
            return $fetched;
        }
        
        
        if ($div_algo && $div_NH) {
            $status = 'unknown';
            $log = 'div_algo div_NH both found';
            return $fetched;
        }
        
        
        
        
        // check head
        $div_algo_head = $div_algo->children(0);
        if (!$div_algo_head) {
            $status = 'unknown';
            $log = 'div_algo_head not found';
            return $fetched;
        }
        
        // check head value
        if ($div_algo_head->innertext !== 'Web Results') {
            $status = 'unknown';
            $log = 'div_algo_head not match: ' . $div_algo_head->innertext;
            return $fetched;
        }
        
        
        // check ul
        $div_algo_ul = $div_algo->children(1);
        if (!$div_algo_ul) {
            $status = 'unknown';
            $log = 'div_algo_ul not found';
            return $fetched;
        }
        
        if ($div_algo_ul->tag !== 'ul') {
            $status = 'unknown';
            $log = 'div_algo_ul is not ul';
            return $fetched;
        }
        
        if ($div_algo_ul->getAttribute('content') !== 'ALGO') {
            $status = 'unknown';
            $log = 'div_algo_ul attribute content is not ALGO: ' . $div_algo_ul->getAttribute('content');
            return $fetched;
        }
        
        
        
        // start parse result
        $ret = $div_algo_ul->find('li[about]');
        
        
    
        if (count($ret) === 0) {
            $status = 'unknown';
            $log = 'result table is empty';
            return $fetched;
        }
        
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value->find('a[rel="f:url"]', 0);
            
            $href = $l->href;
            
            $title = $l->plaintext;
            
            $s = $value->find('p[property="f:desc"]', 0);
            $des = $s->plaintext;
            
            // if not valid values, skip
            if (! ($href && $title && $des)) {
                continue;
            }
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        
        if (count($fetched) === 0) {
            $status = 'unknown';
            $log = 'escaped sus, no valid values left. original ret:' . count($ret);
            return $fetched;
        }
        
        
        
        $status = 'succeed';
        $log = 'result succeed';
        
        
        return $fetched;
    }

    
    
    // add detailed log to status_log.
    function add_status_log($link, $search_handler, $q, $url, $output, $status, &$log) {
    
        $ip= $_SERVER["REMOTE_ADDR"];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $handler = $search_handler['handler'];
        $word = addslashes($q);
        $str = addslashes($url);
        
        date_default_timezone_set("Asia/Hong_Kong");
        $resulttime =date("Y/m/d") . ' ' . date('H:i:s') ;
        $data = addslashes($output);
        
        
        $result = mysql_query("SELECT id FROM status_log ORDER BY id DESC LIMIT 1", $link);
        $row = mysql_fetch_array($result);
        $id = 1;
        if ($row) {
            $id = intval($row['id']) + 1;
        }
        
        $adddata = mysql_query("INSERT INTO status_log(ip_address, user_agent, handler, word, str, resulttime, status, log, data) VALUES('$ip', '$user_agent', '$handler', '$word', '$str', '$resulttime', '$status', '$log', '$data')", $link);
        
        $log = $log . ', status_log_id: ' . $id;
    
    }
    
    
    
    
    
    // start parse result
    function fetch_result($link, $search_handler, $q, $str, &$status, &$log) {
        
        $fetched = array();
        $url = "";
        $cookie_str = "";
        
        
        // create curl resource
        $ch = curl_init();
    
        
        
        // PROXY type href
        if ($search_handler['handler'] === "proxy") {
            
            $url = "https://www.google.com/search?num=40&q=" . $str;
            $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
            
            curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            if ($search_handler['proxy_type'] === "socks5") {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }
        
        else {
            $url = $search_handler['mirror_prefix'] . $str;
            curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 1);
            
            
            // prefetch cookie
            $url_host = get_url_name($url);
            if ($url_host === 'www.psea.in' || $url_host === 'www.ppx.pw' || $url_host === 'www.iamgg.pw') {
                $url_base = get_url_base($url);
                $cookie_str = prefetch_cookie($url_base);
            }
            
        }
        
        
      
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // set cookie.
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        
        // add v4 resolver, to solve potential "Resolving timed out" issue
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        
        
        // use static user agent to avoid constant unknown issue
        // $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36';
        
        $user_agent_client = $_SERVER['HTTP_USER_AGENT'];
        $os = parseOS($user_agent_client);
        $browser = parseBrowser($user_agent_client);
        if ($status === '') {
            if ($browser === 'Firefox' || $browser === 'Safari' || $browser === 'Chrome' || $os === 'iPhone' || $os === 'Android' || $os === 'iPad') {
                $user_agent = $user_agent_client;
            }
        }
        
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        // $output contains the output string
        $output = curl_exec($ch);
        

        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = $estr;
            return $fetched;
        }
         
         
         
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        
        // check whether robot
        $robot_exp = "sorry/IndexRedirect";
        if (strpos($last_url, $robot_exp) !== false) {
            $status = 'robot';
            $log = 'robot';
            return $fetched;
        }
        
        
        
        // close curl resource to free up system resources
        curl_close($ch);
         
        

        
        // parse the html page
        $html = str_get_html($output);
        
        $ret = $html->find('.g');
        
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $redirect_form = $html->find('form[action=CaptchaRedirect]', 0);
            if ($redirect_form) {
                $status = 'robot';
                $log = 'robot captcha found';
                return $fetched;
            }
            
            
            $title_tag = $html->find('title', 0);
            if ($title_tag) {
                $title = $title_tag->plaintext;
                if ($title === "Sorry...") {
                    $status = 'robot';
                    $log = 'robot automated warning found';
                    return $fetched;
                }
            }
            
            
            
            $nofound_msgs = $html->find('.mnr-c', 0);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
                return $fetched;
            }
            
            
            $nofound_msgs2 = $html->find('ul._Gnc', 0);
            if ($nofound_msgs2) {
                $status = 'empty';
                $log = 'empty2';
                return $fetched;
            }
            
            $nofound_msgs3 = $html->find('#resultStats', 0);
            if ($nofound_msgs3) {
                $result_stats = $nofound_msgs3->plaintext;
                if (strpos($result_stats, ' 0 ') !== false) {
                    $status = 'empty';
                    $log = 'stats_zero';
                    return $fetched;
                }
            }
            
            
            
            
            // fall back to unknown and log.
            $status = 'unknown';
            $log = 'nothing found';
            
            
            // add cookie log
            $url_host = get_url_name($url);
            if ($url_host === 'www.psea.in' || $url_host === 'www.ppx.pw' || $url_host === 'www.iamgg.pw') {
                $log = $log . ', cookie_str: ' . $cookie_str;
            }
            
            
            add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            
            return $fetched;
        }
        
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value->find('a', 0);
            
            $href = $l->href;
            
            
            // for ie specific,  to remove search engine prefix;
            if (strpos($href,"/url?url=") !== false) {
                $href = end(explode("/url?url=", $href, 2));
                $href = rawurldecode($href);
            }
            
            
            // another prefix mask, (mandatory for elgoog)
            if (strpos($href,"/url?q=") !== false) {
                $href = end(explode("/url?q=", $href, 2));
                $href = rawurldecode($href);
            }
            

            
            // check whether mirror masked
            if ($search_handler['handler'] === 'mirror' && get_url_name($href) === get_url_name($search_handler['mirror_prefix']) ) {
                $status = 'error';
                $log = 'mirror masked';
                
                add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
                $fetched = array();
                return $fetched;
            }
            
            // check whether local prefix masked
            if (strcasecmp(substr($href, 0, 4), 'http') !== 0) {
                $status = 'error';
                $log = 'local prefix masked: ' . $href . ' ';
                
                add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
                $fetched = array();
                return $fetched;
            }
            
            
            /*
            // check whether whether site filter functional
            if (!check_filtered_url($href)) {
                // temp log
                add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
                continue;
            }
            */
            
            
            
            $title = $l->plaintext;
            
            $s = $value->find('.st', 0);
            $des = $s->plaintext;
            
            // json precheck
            $title = precheck_json($title);
            $des = precheck_json($des);
            
            // if not valid values, skip
            if (! ($href && $title && $des)) {
                continue;
            }
            
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        if (count($fetched) === 0) {
            $status = 'error';
            $log = 'no valid result values. original ret:' . count($ret);
            
            add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        if (strlen(rawurlencode(json_encode($fetched))) < 50) {
            $status = 'error';
            $log = 'insufficient data. fetched:' . json_encode($fetched);
            
            $url_host = get_url_name($url);
            if ($url_host === 's.elgoog.im') {
                $log = $log . ', already awared';
                return $fetched;
            }
            add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        $status = 'succeed';
        $log = count($fetched) . ' (' . strlen(rawurlencode(json_encode($fetched))) . ')';
        
        
        if ($user_agent !== $user_agent_client) {
            if (!$user_agent_client) { $user_agent_client = 'empty'; }
            $log = $log . ', User Agent: ' . $user_agent_client;
        }

        
        return $fetched;
        
        
    }
    
    
    
    
    function addword($link, $word, $data, $status)
    {
        
        // gather user data
        $ip= $_SERVER["REMOTE_ADDR"];
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime =date("Y/m/d") . ' ' . date('H:i:s') ;
        
        
        $slashed_word= addslashes($word);
        $slashed_data= addslashes($data);
        
        if (strlen($slashed_word) > 190) { return; }
        if ( (strlen($slashed_data) < 100  && $status !== 'empty') || strlen($slashed_data) > 65500) { return; }
        
        // query and update words
        $query_str = "SELECT id FROM words WHERE word='$slashed_word' LIMIT 1";
        $result = mysql_query($query_str, $link);
        $row = mysql_fetch_array( $result );
        if ($row) {
            
            $wordid = $row['id'];
            
            $result = mysql_query("SELECT data FROM words WHERE id=$wordid", $link);
            $row = mysql_fetch_array( $result );
            
            if (strlen($row['data']) < 100) {
                if ($status === 'empty') {
                    $updatedata = mysql_query("UPDATE words SET ip_address='$ip', count=count+1, lasttime='$datetime', status='$status'  WHERE id=$wordid ", $link) ;
                } else {
                    $updatedata = mysql_query("UPDATE words SET ip_address='$ip', count=count+1, lasttime='$datetime', data='$slashed_data'  WHERE id=$wordid ", $link) ;
                }
            }
            
        }
        else {
            if ($status === 'empty') {
                $adddata = mysql_query("INSERT INTO words(ip_address, word, count, loaded, addtime, lasttime, status) VALUES('$ip', '$slashed_word', 0, 0, '$datetime', '$datetime', '$status')", $link);
            } else {
                $adddata = mysql_query("INSERT INTO words(ip_address, word, count, loaded, addtime, lasttime, data) VALUES('$ip', '$slashed_word', 0, 0, '$datetime', '$datetime', '$slashed_data')", $link);
            }
        }
        
    }
    
    
    
    function delword($link, $word) {
        
        $slashed_word= addslashes($word);
        
        if (strlen($slashed_word) > 190) { return; }
        $deldata = mysql_query("DELETE FROM words WHERE word='$slashed_word'", $link);
        return;
        
    }
    
    
    

    
    // get parameters from URL
    $q = rawurldecode($_REQUEST["q"]);
    $forced = rawurldecode($_REQUEST["f"]);
    
    
    // result_structure
    $result_array = array(
                        'status' => 'invalid untouched',
                        'log' => '',
                        'data' => '',
                        'match' => '0',
                        'weibo' => '0',
                        );

    
    
    
    // check parameters
    if ((!isset($q)) || ($q==='')) {
        $result_array['status'] = 'invalid query';
        echo json_encode($result_array);
        exit;
    }
    else {
        $q = word_optimize($q);
    }
    
    
    
    
    // generate query str
    $s = get_query_str($q);
     
    // open dblink
    $link = connect_dblink();
    
    
    
    // check cache first    //
    //////////////////////////
    $cache_data = "";
    $cache_status = "";
    
    if ($forced === 'true') {
        delword($link, $q);
    }
    else {
        $cache_data = fetchdata($link, $q, $cache_status);
    }
    
    
    
    
    if ($cache_data !== "" || $cache_status === 'empty') {
        
        if ($cache_status !== 'empty') {
            $result_array['status'] = 'cached';
            $result_array['data'] = rawurlencode($cache_data);
        }
        else {
            $result_array['status'] = 'empty';
            $result_array['log'] = 'cache empty';
            $result_array['data'] = rawurlencode($cache_data);
        }
        
    }
    else {
        
        $lastday_val = intval(count_lastday($link));
        
        if ($lastday_val >= 30) {
            $result_array['status'] = 'exceed_user';
            $result_array['log'] = $_SERVER["REMOTE_ADDR"];
            add_lastday($link);
        }
        else
        {
            
            // add lastday count
            add_lastday($link);
            
            // start fetch new data //
            //////////////////////////
            
            $fetched = array();
            $status = '';
            $log = '';
            
            
            $search_handler_array = get_search_handler($link);
            
            if(!$search_handler_array) {
                $status = 'nohandler';
                $log = 'nohandler';
            }
            
            else {
                
                $fetched = fetch_result($link, $search_handler_array[0], rawurlencode($q), rawurlencode($s), $status, $log);
                update_search_handler($link, $search_handler_array[0], $status, $log);
                
                
                
                // round2 check
                if ($status !== 'succeed' && $status !=='empty') {
                    
                    $log_round2 = '';
                    
                    //  pick active handlers
                    $search_handler_array = get_search_handler($link, 'active');
                    $round2_handler_count = count($search_handler_array);
                    
                    if(!$search_handler_array) {
                        $status = 'nohandler';
                        $log_round2 = 'nohandler';
                    }
                    else {
                        $fetched = fetch_result($link, $search_handler_array[0], rawurlencode($q), rawurlencode($s), $status, $log_round2);
                        update_search_handler($link, $search_handler_array[0], $status, $log_round2);
                    }
                    
                    $log = 'round1 ' . $log . '; round2 ' . $log_round2;
                    
                    
                    
                    // round3 check
                    if ($status !== 'succeed' && $status !=='empty' && $round2_handler_count > 5) {
                        
                        $log_round3 = '';
                        
                        //  pick active handlers
                        $search_handler_array = get_search_handler($link, 'active');
                        
                        if(!$search_handler_array) {
                            $status = 'nohandler';
                            $log_round3 = 'nohandler';
                        }
                        else {
                            $fetched = fetch_result($link, $search_handler_array[0], rawurlencode($q), rawurlencode($s), $status, $log_round3);
                            update_search_handler($link, $search_handler_array[0], $status, $log_round3);
                        }
                        
                        $log = $log . '; round3 ' . $log_round3;
                    }
                    
                    
                }
                
            }
            
            
            // data dealing
            
            $result_array['status'] = $status;
            $result_array['log'] = $log;
            $result_array['data'] = rawurlencode(json_encode($fetched));
            
            // for some encoding issue on client side cache, cache in server side.
            addword($link, $q, json_encode($fetched), $status);
            
            
        }
        
        
        
    }
    
    
    // close dblink
    close_dblink($link);
    
    
    if ($result_array['status'] === 'cached' || $result_array['status'] === 'succeed') {

        $fetched = fetched_crops( json_decode( rawurldecode($result_array['data']) ) );
        $result_array['data'] = rawurlencode( json_encode($fetched) );
        
        if (count($fetched) === 0) {
            $result_array['log'] = $result_array['log'] . ', empty after croped: (' . $result_array['status'] . ')';
            $result_array['status'] = 'empty';
        }
        
        // count exact match
        exact_match_count($fetched, $q, $matches, $weibo);
        $result_array['match'] = strval($matches);
        $result_array['weibo'] = strval($weibo);
        
        
    }
    echo json_encode($result_array);

    
    



