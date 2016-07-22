<?php
    
    
    // prevent non AJAX call.
    if(!$_SERVER['HTTP_X_REQUESTED_WITH'])
    {
        header("HTTP/1.0 403 Forbidden");
        exit;
    }
    
    
    
    // lagency: google ips management and validating ,
    // lagency: t*e*m*p solution (furing require dynamic checking on serversite with strategies)
    
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
    
    
    
    function fetchdata_normal($link, $word) {
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
            
            $result = mysql_query("SELECT data FROM words WHERE id=$wordid", $link);
            $row = mysql_fetch_array( $result );
            
            if (strlen($row['data'])>100) {
                $fetched = $row['data'];
            }
        }
        else {
            
        }
        
        
        return $fetched;
    }
    
    
    
    function fetchdata($link, $db, $word, &$cache_status)
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
        $query_str = "SELECT id FROM $db WHERE word='$slashed_word' LIMIT 1";
        $result = mysql_query($query_str, $link);
        $row = mysql_fetch_array( $result );
        if ($row) {
            
            $wordid = $row['id'];
            
            $result = mysql_query("SELECT data, status FROM $db WHERE id=$wordid", $link);
            $row = mysql_fetch_array( $result );
            
            if (strlen($row['data'])>100) {
                $fetched = $row['data'];
                
                $updatedata = mysql_query("UPDATE $db SET ip_address='$ip', loaded=loaded+1, lasttime='$datetime' WHERE id=$wordid ", $link) ;
            }
            else {
                if ($row['status'] === 'empty') {
                    $cache_status = 'empty';
                    $fetched = $row['data'];
                    $updatedata = mysql_query("UPDATE $db SET ip_address='$ip', loaded=loaded+1, lasttime='$datetime' WHERE id=$wordid ", $link) ;
                }
            }
        }
        else {
            $adddata = mysql_query("INSERT INTO $db(ip_address, word, count, loaded, addtime, lasttime) VALUES('$ip', '$slashed_word', 0, 0, '$datetime', '$datetime')", $link);
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
    
    
    function get_search_handler($link, $db_proxy, $type = 'normal') {
        
        
        // current time
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
        
        // $mod_offset = '-' . '6' . ' minute';
        $mod_offset = '-' . '1' . ' minute';
        $datetime_mod = date("Y/m/d H:i:s", strtotime($mod_offset));
        
        
        $results = array();
        
        
        
        $result_proxy_query_str =  "SELECT * FROM $db_proxy WHERE lasttime<'$datetime_mod' ORDER BY lasttime";
        if ($type === 'active') {
            $result_proxy_query_str =  "SELECT * FROM $db_proxy WHERE lasttime<'$datetime_mod' AND sick=0 ORDER BY lasttime";
        }
        $result_proxy = mysql_query($result_proxy_query_str, $link);
        while ($row_proxy = mysql_fetch_array($result_proxy)) {
            
            $search_handler = array(
                                    "handler" => 'kat_proxy',
                                    "handler_id" => $row_proxy['id'],
                                    "mirror_prefix" => '',
                                    "proxy_address" => $row_proxy['address'],
                                    "proxy_port" => $row_proxy['port'],
                                    "proxy_type" => $row_proxy['type'],
                                  //   "backup_prefix" => 'http://libgen.io/search.php?req=',
                                  //2   "backup_prefix" => 'http://gen.lib.rus.ec/search.php?req=',
                                  //   "backup_prefix" => 'http://libgen.io/search.php?req=',
                                    "backup_prefix" => 'https://kat.cr/usearch/',
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
            $adddata = mysql_query("UPDATE $db_proxy SET lasttime='$datetime' , total=total+1, total_day=total_day+1 WHERE id=$id", $link);
        }
    
    
        return $results;

    }
    
    
    
    function update_search_handler($link, $db_proxy, $search_handler, $status, $log) {

        $id = $search_handler['handler_id'];
        $handler_table = $db_proxy;
        
        
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
               
                $mod_offset = '+' . $mod_sick * 30 . ' minute';
                $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, error=error+1, error_day=error_day+1, log='$new_log' WHERE id=$id", $link);
                break;
        
            case "robot":
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, robot=robot+1, robot_day=robot_day+1, log='$new_log'  WHERE id=$id", $link);
                break;
        
            case "unknown":
                
                $mod_offset = '+' . $mod_sick * 30 . ' minute';
                $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                
                $adddata = mysql_query("UPDATE $handler_table SET lasttime='$datetime_mod' , sick=$mod_sick, unknown=unknown+1, unknown_day=unknown_day+1, log='$new_log'  WHERE id=$id", $link);
                break;
        
            case "empty":
                $adddata = mysql_query("UPDATE $handler_table SET sick=0, empty=empty+1, empty_day=empty_day+1, log='$new_log'  WHERE id=$id", $link);
                
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
    
    
    
    
    
    function fetch_backup($link, $search_handler, $q, $s, &$status, &$log) {
        
        
        $host_name = get_url_name($search_handler['backup_prefix']);

        $str = '';
        
        // parse weibo
        if ($host_name === 'vdisk.weibo.com') {
            $str = $q;
        }
        
        if ($host_name === 'www.bing.com') {
            $str = $s;
        }
        
        if ($host_name === 'libgen.io' || $host_name === 'gen.lib.rus.ec') {
            $str = $q;
        }
        
        if ($host_name === 'kat.cr') {
            $str = $q;
        }
        
        
        $fetched = array();
        $url = $search_handler['backup_prefix'] . $str . '%20category:books/';
        
        
        
        // create curl resource
        $ch = curl_init();
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if ($search_handler['proxy_type'] === "socks5") {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // set cookie.
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        curl_setopt( $ch, CURLOPT_ENCODING, '');
        
        // use static user agent to avoid constant unknown issue, try & test
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
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
        
        
        // parse weibo
        if ($host_name === 'vdisk.weibo.com') {
            $fetched = parse_weibo($output, $status, $log);
        }
        
        
        // parse aol && wow
        if ($host_name === 'www.bing.com') {
            $fetched = parse_bing($output, $status, $log);
        }
        
        
        // parse eng
        if ($host_name === 'libgen.io' || $host_name === 'gen.lib.rus.ec') {
            $fetched = parse_eng($output, $status, $log, $str);
        }
        
        // parse kat
        if ($host_name === 'kat.cr') {
            $fetched = parse_kat($output, $status, $log, $str);
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
    
    
    
    
    function parse_bing($output, &$status, &$log) {
        
        
        
        $fetched = array();
        
        $html = str_get_html($output);
        
        $ret = $html->find('.b_algo');
        
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $nofound_msgs = $html->find('.b_no', 0);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
            }
            else {
                $status = 'unknown';
                $log = 'unknown';
                
                // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            }
            
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
            
            
            // check whether local prefix masked
            if (strcasecmp(substr($href, 0, 4), 'http') !== 0) {
                $status = 'error';
                $log = 'local prefix masked: ' . $href . ' ';
                
                // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
                $fetched = array();
                return $fetched;
            }
            
            
            
            $title = $l->plaintext;
            
            $s = $value->find('p', 0);
            $des = $s->plaintext;
            
            // if not valid values, skip
            if (! ($href && $title && $des)) {
                continue;
            }
            
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        if (count($fetched) === 0) {
            $status = 'error';
            $log = 'no valid result values. original ret:' . count($ret);
            
            // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        if (strlen(rawurlencode(json_encode($fetched))) < 50) {
            $status = 'error';
            $log = 'insufficient data. fetched:' . json_encode($fetched);
            
            // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        $status = 'succeed';
        $log = count($fetched) . ' (' . strlen(rawurlencode(json_encode($fetched))) . ')';
        
        
        return $fetched;
    }
    
    
    
    function parse_eng($output, &$status, &$log, $str) {
        
        $word = rawurldecode($str);
        
        $fetched = array();
        
        $html = str_get_html($output);
        
        $ret = $html->find('a[href^="book/index.php?md5="]');
        
        
        // check result existance
        if (count($ret) === 0) {
            
            
            $nofound_msgs = (strpos($html, '0 books found') !== false);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
            }
            else {
                $status = 'unknown';
                $log = 'unknown';
                
                // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            }
            
            return $fetched;
            
        }
        
        
        
        
        foreach ($ret as $value) {
            $li = array();
            $l = $value;
            
            // $href = 'http://libgen.io/' . $l->href;
            //2 $href = 'http://gen.lib.rus.ec/' . $l->href;
            $href = 'http://libgen.io/' . $l->href;

            
            // check whether local prefix masked
            if (strcasecmp(substr($href, 0, 4), 'http') !== 0) {
                $status = 'error';
                $log = 'local prefix masked: ' . $href . ' ';
                
                // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
                $fetched = array();
                return $fetched;
            }
            
            
            
            $title = $l->find('text',0)->plaintext;
            
            $s = $value->parent()->parent();
            $author = htmlspecialchars($s->children(1)->plaintext);
            $publisher = htmlspecialchars($s->children(3)->plaintext);
            $year = htmlspecialchars($s->children(4)->plaintext);
            $page = htmlspecialchars($s->children(5)->plaintext);
            $language = htmlspecialchars($s->children(6)->plaintext);
            $size = htmlspecialchars($s->children(7)->plaintext);
            $extension = htmlspecialchars($s->children(8)->plaintext);
            
            
            $des = 'Author: '.$author.'; Publisher: '.$publisher.'; Year: '.$year.'; Pages: '.$page.'; Language: '.$language.'; Size: '.$size;
            
            
            if (stripos($title, $word) !== false) {
                $title = $title . ' .' . $extension;
            }
            else{
                $title = $title . ' - ' . $author . ' (' . $extension . ')';
            }
            
            
            // if not valid values, skip
            if (! ($href && $title && $des)) {
                continue;
            }
            
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        if (count($fetched) === 0) {
            $status = 'error';
            $log = 'no valid result values. original ret:' . count($ret);
            
            // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        if (strlen(rawurlencode(json_encode($fetched))) < 50) {
            $status = 'error';
            $log = 'insufficient data. fetched:' . json_encode($fetched);
            
            // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        $status = 'succeed';
        $log = count($fetched) . ' (' . strlen(rawurlencode(json_encode($fetched))) . ')';
        
        
        return $fetched;
    }

    
    
    function parse_kat($output, &$status, &$log, $str) {
        
        $word = rawurldecode($str);
        
        $fetched = array();
        
        $html = str_get_html($output);
        
        $ret = $html->find('tr[id^=torrent_]');
        
        
        
        // check result existance
        if (count($ret) === 0) {
            
            $nofound_msgs = $html->find('.errorpage', 0);
            if ($nofound_msgs) {
                $status = 'empty';
                $log = 'empty';
            }
            else {
                $status = 'unknown';
                $log = 'unknown';
                
                // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            }
            
            return $fetched;
            
        }
        
        
        
        
        foreach ($ret as $value) {
            $l = $value->find('a.cellMainLink', 0);
            
            // $href = 'http://libgen.io/' . $l->href;
            //2 $href = 'http://gen.lib.rus.ec/' . $l->href;
            // $href = 'https://kat.cr' . $l->href;
            $l_mag = $value->find('a[href^=magnet]', 0);
            $href = $l_mag->href;
            
            
            $title = $l->plaintext;
            
            $tds = $value->find('td');
            $td_size = 'Size:' . ($tds[1]? trim($tds[1]->plaintext) : '');
            $td_files = 'Files:' . ($tds[2]? trim($tds[2]->plaintext) : '');
            $td_age = 'Created Time:' . ($tds[3]? trim($tds[3]->title) : '');
            $td_seed = 'Seeds:' . ($tds[4]? trim($tds[4]->plaintext) : '');
            
            $des = 'Torrent - ' . $td_size . '; ' . $td_files . '; ' . $td_age . '; ' . $td_seed;
            
            // if not valid values, skip
            if (! ($href && $title && $des)) {
                continue;
            }
            
            
            $fetched[] = array($href, $title, $des);
        }
        
        
        if (count($fetched) === 0) {
            $status = 'error';
            $log = 'no valid result values. original ret:' . count($ret);
            
            // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        if (strlen(rawurlencode(json_encode($fetched))) < 50) {
            $status = 'error';
            $log = 'insufficient data. fetched:' . json_encode($fetched);
            
            // add_status_log($link, $search_handler, $q, $url, $output, $status, $log);
            return $fetched;
        }
        
        
        $status = 'succeed';
        $log = count($fetched) . ' (' . strlen(rawurlencode(json_encode($fetched))) . ')';
        
        
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
    
    
    
    
    function addword($link, $db, $word, $data, $status)
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
        $query_str = "SELECT id FROM $db WHERE word='$slashed_word' LIMIT 1";
        $result = mysql_query($query_str, $link);
        $row = mysql_fetch_array( $result );
        if ($row) {
            
            $wordid = $row['id'];
            
            $result = mysql_query("SELECT data FROM $db WHERE id=$wordid", $link);
            $row = mysql_fetch_array( $result );
            
            if (strlen($row['data']) < 100) {
                if ($status === 'empty') {
                    $updatedata = mysql_query("UPDATE $db SET ip_address='$ip', count=count+1, lasttime='$datetime', status='$status'  WHERE id=$wordid ", $link) ;
                } else {
                    $updatedata = mysql_query("UPDATE $db SET ip_address='$ip', count=count+1, lasttime='$datetime', data='$slashed_data'  WHERE id=$wordid ", $link) ;
                }
            }
            
        }
        else {
            if ($status === 'empty') {
                $adddata = mysql_query("INSERT INTO $db(ip_address, word, count, loaded, addtime, lasttime, status) VALUES('$ip', '$slashed_word', 0, 0, '$datetime', '$datetime', '$status')", $link);
            } else {
                $adddata = mysql_query("INSERT INTO $db(ip_address, word, count, loaded, addtime, lasttime, data) VALUES('$ip', '$slashed_word', 0, 0, '$datetime', '$datetime', '$slashed_data')", $link);
            }
        }
        
    }
    

    // initial db source
    $db = 'wordskat';
    $db_proxy = 'wordskat_proxy';
    
    
    
    // get parameters from URL
    $q = rawurldecode($_REQUEST["q"]);
    $s = "";
    
    
    
    
    // result_structure
    $result_array = array(
                        'status' => 'invalid untouched',
                        'log' => '',
                        'data' => '',
                        );
    
    
    // maintenance pause
    $maintenance_pause = true;
    if ($maintenance_pause) {
        $result_array['status'] = 'maintenance pause';
        echo json_encode($result_array);
        exit;
    }

    
    
    // check parameters
    if ((!isset($q)) || ($q==='')) {
        $result_array['status'] = 'invalid query';
        echo json_encode($result_array);
        exit;
    }
    else {
        $q = word_optimize($q, 30);
    }
    
    
    if (strlen($q) < 4) {
        $result_array['status'] = 'invalid word_length';
        echo json_encode($result_array);
        exit;
    }
    
    
     
    // open dblink
    $link = connect_dblink();
    
    
    
    // check cache first    //
    //////////////////////////
    
    
    // check cache normal
    /*
    $cache_data_normal = fetchdata_normal($link, $db, $q);
    if ($cache_data_normal) {
        $cache_data_normal_array = json_decode($cache_data_normal);
        if (count($cache_data_normal_array) >= 20) {
            $result_array['status'] = 'normalcached_valid';
            close_dblink($link);
            echo json_encode($result_array);
            exit;
        }
    }
    */
    
    
    
    $cache_status = "";
    $cache_data = fetchdata($link, $db, $q, $cache_status);
    
    
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
            
            if ($lastday_val > 30) {
                $result_array['status'] = 'exceed_user';
                $result_array['log'] = $_SERVER["REMOTE_ADDR"];
            }
            else
            {
                
                // start fetch new data //
                //////////////////////////
                
                $fetched = array();
                $status = '';
                $log = '';
                
                
                $search_handler_array = get_search_handler($link, $db_proxy);
                
                
                
                if(!$search_handler_array) {
                    $status = 'nohandler';
                    $log = 'nohandler';
                }
                
                else {

                    $fetched = fetch_backup($link, $search_handler_array[0], rawurlencode($q), rawurlencode($s), $status, $log);
                    
                    
                    update_search_handler($link, $db_proxy, $search_handler_array[0], $status, $log);
                    
                    
                    
                    // round2 check
                    if ($status !== 'succeed' && $status !=='empty') {
                        
                        $log_round2 = '';
                        
                        //  pick active handlers
                        $search_handler_array = get_search_handler($link, $db_proxy, 'active');
                        $round2_handler_count = count($search_handler_array);
                        
                        if(!$search_handler_array) {
                            $status = 'nohandler';
                            $log_round2 = 'nohandler';
                        }
                        else {
                            $fetched = fetch_backup($link, $search_handler_array[0], rawurlencode($q), rawurlencode($s), $status, $log_round2);
                            update_search_handler($link, $db_proxy, $search_handler_array[0], $status, $log_round2);
                        }
                        
                        $log = 'round1 ' . $log . '; round2 ' . $log_round2;

                    }
                     
                
                    
                }
                
              
                // data dealing
                
                $result_array['status'] = $status;
                $result_array['log'] = $log;
                $result_array['data'] = rawurlencode(json_encode($fetched));
                
                
                // for some encoding issue on client side cache, cache in server side.
                addword($link, $db, $q, json_encode($fetched), $status);
          
                
        }

        

    }
    
    
    
    // close dblink
    close_dblink($link);
    echo json_encode($result_array);

    
    



