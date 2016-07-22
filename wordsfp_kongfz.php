<?php
    
    /*
    // prevent non AJAX call.
    if(!$_SERVER['HTTP_X_REQUESTED_WITH'])
    {
        header("HTTP/1.0 403 Forbidden");
        exit;
    }
    */
    
    
    // lagency: google ips management and validating ,
    // lagency: t*e*m*p solution (furing require dynamic checking on serversite with strategies)
    
    include_once ('header_config.php');
    include_once ('header_crops.php');
    include_once ('hitcounter/counter_config.php');
    include_once ('simple_html_dom.php');
    
    
    /**
     * (https://github.com/wangchll/PHP-Unicode-Convert)
     * 提供GBK,UTF8转化为Unicode编码,
     * Unicode转化为GBK,UTF8编码字符串类库
     *
     * @author wang chong(wangchong1985@gmail.com)
     * @link http://www.wangchong.org
     * @version 1.0.0 (2011-04-15)
     * @package php-Unicode
     */
    class Unicode
    {
        /**
         * 自定义str_to_unicode后的连接符
         * @var string
         */
        public $glue = "";
        /**
         * 将字符串转换成unicode编码
         *
         * @param string $input
         * @param string $input_charset
         * @return string
         */
        public function str_to_unicode($input, $input_charset = 'gbk')
        {
            $input = iconv($input_charset, "gbk", $input);
            preg_match_all("/[\x80-\xff]?./", $input, $ar);
            $b = array_map(array($this, 'utf8_unicode_'), $ar[0]);
            $outstr = join($this->glue, $b);
            return $outstr;
        }
        private function utf8_unicode_($c, $input_charset = 'gbk')
        {
            $c = iconv($input_charset, 'utf-8', $c);
            return $this->utf8_unicode($c);
        }
        // utf8 -> unicode
        private function utf8_unicode($c)
        {
            switch(strlen($c)) {
                case 1:
                    //return $c;
                    $n = ord($c[0]);
                    break;
                case 2:
                    $n = (ord($c[0]) & 0x3f) << 6;
                    $n += ord($c[1]) & 0x3f;
                    break;
                case 3:
                    $n = (ord($c[0]) & 0x1f) << 12;
                    $n += (ord($c[1]) & 0x3f) << 6;
                    $n += ord($c[2]) & 0x3f;
                    break;
                case 4:
                    $n = (ord($c[0]) & 0x0f) << 18;
                    $n += (ord($c[1]) & 0x3f) << 12;
                    $n += (ord($c[2]) & 0x3f) << 6;
                    $n += ord($c[3]) & 0x3f;
                    break;
            }
            return "k".base_convert($n, 10, 16);
        }
        /**
         * 将unicode字符转换成普通编码字符
         *
         * @param string $str
         * @param string $out_charset
         * @return string
         */
        public function str_from_unicode($str, $out_charset = 'gbk')
        {
            $str = preg_replace_callback("|k([0-9a-f]{1,4})|", array($this, 'unicode2utf8_'), $str);
            $str = iconv("UTF-8", $out_charset, $str);
            return $str;
        }
        private function unicode2utf8_($c)
        {
            return $this->unicode2utf8($c[1]);
        }
        private function unicode2utf8($c)
        {
            $c = base_convert($c, 16, 10);
            $str="";
            if ($c < 0x80) {
                $str.=chr($c);
            } else if ($c < 0x800) {
                $str.=chr(0xC0 | $c>>6);
                $str.=chr(0x80 | $c & 0x3F);
            } else if ($c < 0x10000) {
                $str.=chr(0xE0 | $c>>12);
                $str.=chr(0x80 | $c>>6 & 0x3F);
                $str.=chr(0x80 | $c & 0x3F);
            } else if ($c < 0x200000) {
                $str.=chr(0xF0 | $c>>18);
                $str.=chr(0x80 | $c>>12 & 0x3F);
                $str.=chr(0x80 | $c>>6 & 0x3F);
                $str.=chr(0x80 | $c & 0x3F);
            }
            return $str;
        }
    }
    
    
    
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
                                    "handler" => 'p_kongfz_proxy',
                                    "handler_id" => $row_proxy['id'],
                                    "mirror_prefix" => '',
                                    "proxy_address" => $row_proxy['address'],
                                    "proxy_port" => $row_proxy['port'],
                                    "proxy_type" => $row_proxy['type'],
                                    "backup_prefix" => 'http://search.kongfz.com/product/y0z',
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
    
    
    
    
    
    function fetch_backup($link, $search_handler, $q_u, $q, &$status, &$log) {
        
        
        $host_name = get_url_name($search_handler['backup_prefix']);

        
        $str = $q_u;
        
        
        $fetched = array();
        $url = $search_handler['backup_prefix'] . $str;
        
        $url = 'http://search.kongfz.com/product/y0zk68/';
        
        $url = 'http://search.kongfz.com/product/y0z' . $str;
        
    
        // echo 'url: ' . $url;
        
        $cookie_str = '';
        
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
        
        
        
        
        // close curl resource to free up system resources
        curl_close($ch);
        
        
      
        $fetched = parse_p_kongzf($output, $status, $log, $q);
        
    
        
        
        return $fetched;
        
        
    }
    

    
    
    function parse_p_kongzf($output, &$status, &$log, $q) {
        
        
        
        
        $fetched = array();
        
        $html = str_get_html($output);
        
        // echo '<br>'.$html.'<br>';
        
        
        
        
        $result_list = $html->find('#mainContent', 0);

        
        $ret = array();
        if ($result_list) {
            $ret = $result_list->find('div.result_box');
        }
        
        
        
       
        
        // check result existance
        if (!$result_list || count($ret) === 0) {
            
            $nofound_msgs = $html->find('div.erro_tit', 0);
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
        
        
        // echo '<br>ret parse successful ' . count($ret);
        
        
        $ret = array_slice($ret, 0, 10);
        foreach ($ret as $value) {
            $price = $value->price;
 
            
            // $img_url = $value->getAttribute('img-url');
            //$pic = 'http://www.kfzimg.com/' . $img_url;
     
            $small_pic_img = $value->find('a.small_pic_img', 0);
            $big_img_url = $small_pic_img->getAttribute('big-img');
            $pic = $big_img_url;
            if ($pic === 'none') {
                $pic = 'http://res.kongfz.com/image/none.gif';
            }
            $pic = str_replace("_b.jpg","_s.jpg",$pic);
            
            $result_tit = $value->find('div.result_tit', 0);
            $result_tit_a = $result_tit->find('a', 0);
            $href = $result_tit_a->href;
            $href = preg_replace("/\/\?ref\=search$/", '', $href);
            
            $title = $result_tit_a->plaintext;
       
            
            $info = $value->find('div.info', 0);
            $des = $info->plaintext;
         
            
            $txt_right1 = $value->find('.txt_right', 0);
            $used = $txt_right1->children(1)->plaintext;
       
            
            $txt_right2 = $value->find('.txt_right', 1);
            $time = $txt_right2->plaintext;
            
            
            
            // echo '<br>temp data: ' . $pic . '; ' . $href . '; ' . $title . '; ' . $des . '; ' . $price . '; ' . $used . '; ' . $time . '; ';
            
            
            // if not valid values, skip
            
            if (! ($pic && $price && $href && $title && $des && $used && $time)) {
                continue;
            }
            
            
            $fetched[] = array($pic, $href, $title, $des, $price, $used, $time);
            
            
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
    $db = 'wordspkongfz';
    $db_proxy = 'wordspkongfz_proxy';
    
    
    
    // get parameters from URL
    $q = rawurldecode($_REQUEST["q"]);
    
    
    
    // result_structure
    $result_array = array(
                        'status' => 'invalid untouched',
                        'log' => '',
                        'data' => '',
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
    // $s = get_query_str($q);
    
    // generate word unicode form
    $convert = new Unicode();
    $str = $convert->str_to_unicode($q, 'UTF-8');
    $q_u = $str . '/';
    
    
    
    // echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"></head><body>--------------op---------<br>';
    
    
    // open dblink
    $link = connect_dblink();
    
    
    
    // check cache first    //
    //////////////////////////
    
    
    
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

                    $fetched = fetch_backup($link, $search_handler_array[0], $q_u, rawurlencode($q), $status, $log);
                    
                    
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
                            $fetched = fetch_backup($link, $search_handler_array[0], $q_u, rawurlencode($q), $status, $log_round2);
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
    
    
    // echo '</body></html>';

    
    



