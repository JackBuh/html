<?php
    
    // comm_lastday
    $page = 'comm_lastday';
    
    include_once ('hitcounter/counter_config.php');
    include_once ('auto_fulfill_proxy.php');
    include_once ('auto_precheck_proxy.php');
    
    ignore_user_abort(true);
    set_time_limit(600);
    
    
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
    
    
    
    function is_newday($link) {
        
        date_default_timezone_set("Asia/Hong_Kong");
        $date = date("Y/m/d");
        $hour = date("H");
        
        
        
        $result = mysql_query("SELECT * FROM info_date", $link);
        $row = mysql_fetch_array($result);
        
        
        
        if(!$row) {
            // ^in case, first time check
            $adddata = mysql_query("INSERT INTO info_date(date, hour) VALUES('$date', '$hour')", $link);
            return true;
        }
        
        else {
            $date_store = $row['date'];
            if ($date > $date_store) {
                $adddata = mysql_query("UPDATE info_date SET date='$date'", $link);
                return true;
            }
            else {
                return false;
            }
            
        }
        
    }
    
    
    
    
    
    function is_newhour($link) {
        
        date_default_timezone_set("Asia/Hong_Kong");
        $date = date("Y/m/d");
        $hourmin = date("H:i");
        $hour = substr($hourmin, 0, 2);
        $minute = substr($hourmin, -2, 2);
        $hourspan = "";
        
        
        // 15 min span
        /*
         if ($minute < '15') {
         $hourspan = $hour . ":00";
         }
         if ($minute >= '15' && $minute < '30') {
         $hourspan = $hour . ":15";
         }
         if ($minute >= '30' && $minute < '45') {
         $hourspan = $hour . ":30";
         }
         if ($minute >= '45') {
         $hourspan = $hour . ":45";
         }
         */
        
        
        // 30 min span
        if ($minute < '30') {
            $hourspan = $hour . ":00";
        }
        if ($minute >= '30') {
            $hourspan = $hour . ":30";
        }
        
        
        
        
        
        $result = mysql_query("SELECT * FROM info_date", $link);
        $row = mysql_fetch_array($result);
        
        
        
        if(!$row) {
            // ^in case, first time check
            $adddata = mysql_query("INSERT INTO info_date(date, hour) VALUES('$date', '$hourspan')", $link);
            return true;
        }
        
        else {
            $hourspan_store = $row['hour'];
            if ($hourspan !== $hourspan_store) {
                $adddata = mysql_query("UPDATE info_date SET hour='$hourspan'", $link);
                return true;
            }
            else {
                return false;
            }
            
        }
    }
    
    
    
    function is_newminute($link) {
        
        date_default_timezone_set("Asia/Hong_Kong");
        $date = date("Y/m/d");
        $hourspan = date("H:i");
        $minutespan = substr($hourspan, -2, 2);
        
        $result = mysql_query("SELECT * FROM info_date", $link);
        $row = mysql_fetch_array($result);
        
        
        if(!$row) {
            // ^in case, first time check
            $adddata = mysql_query("INSERT INTO info_date(date, hour) VALUES('$date', '$hourspan')", $link);
            return true;
        }
        
        else {
            $hourspan_store = $row['hour'];
            if ($hourspan !== $hourspan_store) {
                $adddata = mysql_query("UPDATE info_date SET hour='$hourspan'", $link);
                return true;
            }
            else {
                return false;
            }
            
        }
    }
    
    
    
    
    function update_search_handler($link) {
        
        // update search handler mirror
        $adddata = mysql_query("UPDATE words_href SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy
        $adddata = mysql_query("UPDATE words_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy eng
        $adddata = mysql_query("UPDATE wordseng_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy weibo
        // $adddata = mysql_query("UPDATE wordsweibo_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy bing
        $adddata = mysql_query("UPDATE wordsbing_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy wordskat
        // $adddata = mysql_query("UPDATE wordskat_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy wordspkongfz
        $adddata = mysql_query("UPDATE wordspkongfz_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
        
        // update search handler proxy wordsishare
        $adddata = mysql_query("UPDATE wordsishare_proxy SET total_day=0, succeed_day=0, error_day=0, robot_day=0, unknown_day=0, empty_day=0", $link);
    }
    
    

    
    function update_words_cache($link, $db) {
        
        date_default_timezone_set("Asia/Hong_Kong");
        $date = date("Y/m/d");
        
        
        // delete words order than 90 days
        $mod_offset = '-60 days';
        
        if ($db === 'wordspkongfz') {
            $mod_offset = '-15 days';
        }
        
        
        if ($db === 'wordseng' || $db === 'wordskat' || $db === 'wordsbing' || $db === 'wordsishare') {
            $mod_offset = '-30 days';
        }
        
        $date_mod = date("Y/m/d", strtotime($mod_offset));
        $deldata = mysql_query("DELETE FROM $db WHERE addtime<'$date_mod' OR data IS NULL", $link);
        
        
        // delete frequent words order than 30 days, frequency more than 10
        if ($db === 'words') {
            $mod_offset_frequent = '-30 days';
            $date_mod_frequent = date("Y/m/d", strtotime($mod_offset_frequent));
            $deldata = mysql_query("DELETE FROM $db WHERE addtime<'$date_mod_frequent' AND loaded>10", $link);
        }
        
    }

    
    
    function update_lastday_count($link) {
        
        
        
        // ################################################
        // ######### get lastday count ###########
        // ################################################
        
        
        // current time
        $ip= $_SERVER["REMOTE_ADDR"];
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
        $datetime_mod = date("Y/m/d", strtotime("-1 days")) . ' ' . date('H:i:s', strtotime("-1 days"));
        
        
        // clear user count record <critical!>
        $deldata = mysql_query("DELETE FROM info_lastday", $link);
        
        // clear status result
        $deldata = mysql_query("DELETE FROM status_result", $link);
        
        
    }
    
    
    
    function update_status_log($link) {
        $result = mysql_query("SELECT count(*) as total from status_log", $link);
        $data = mysql_fetch_assoc($result);
        $total = (int)$data['total'];
        
        // empty and reset status log
        if($total >= 1000) {
            $result = mysql_query("TRUNCATE table status_log", $link);
        }
    }
    
    
    function email_healthy_status($link) {
        
        
        date_default_timezone_set("Asia/Hong_Kong");
        $minutespan = date("i");
        
        if (!($minutespan === '00' || $minutespan === '30')) {
            return false;
        }
        
        $alive_words_href = 0;
        $alive_words_proxy = 0;
        $alive_wordseng_proxy = 0;
        // $alive_wordskat_proxy = 0;
        $alive_wordsbing_proxy = 0;
        $alive_wordsishare_proxy = 0;
        $alive_wordspkongfz_proxy = 0;
        
        
        $result = mysql_query("SELECT count(*) as total from words_href where sick=0", $link);
        $data = mysql_fetch_assoc($result);
        $alive_words_href = (int)$data['total'];
        
        $result = mysql_query("SELECT count(*) as total from words_proxy where sick=0", $link);
        $data = mysql_fetch_assoc($result);
        $alive_words_proxy = (int)$data['total'];
        
        $result = mysql_query("SELECT count(*) as total from wordseng_proxy where sick=0", $link);
        $data = mysql_fetch_assoc($result);
        $alive_wordseng_proxy = (int)$data['total'];
        
        // $result = mysql_query("SELECT count(*) as total from wordskat_proxy where sick=0", $link);
        // $data = mysql_fetch_assoc($result);
        // $alive_wordskat_proxy = (int)$data['total'];
        
        $result = mysql_query("SELECT count(*) as total from wordsbing_proxy where sick=0", $link);
        $data = mysql_fetch_assoc($result);
        $alive_wordsbing_proxy = (int)$data['total'];
        
        $result = mysql_query("SELECT count(*) as total from wordsishare_proxy where sick=0", $link);
        $data = mysql_fetch_assoc($result);
        $alive_wordsishare_proxy = (int)$data['total'];
        
        $result = mysql_query("SELECT count(*) as total from wordspkongfz_proxy where sick=0", $link);
        $data = mysql_fetch_assoc($result);
        $alive_wordspkongfz_proxy = (int)$data['total'];
        
        
        $message = "words_href: " . $alive_words_href . "\n";
        $message = $message . "words_proxy: " . $alive_words_proxy . "\n";
        $message = $message . "wordseng_proxy: " . $alive_wordseng_proxy . "\n";
        // $message = $message . "wordskat_proxy: " . $alive_wordskat_proxy . "\n";
        $message = $message . "wordsbing_proxy: " . $alive_wordsbing_proxy . "\n";
        $message = $message . "wordsishare_proxy: " . $alive_wordsishare_proxy . "\n";
        $message = $message . "wordspkongfz_proxy: " . $alive_wordspkongfz_proxy . "\n";
        
        
        // mail('wangzhiwei0101@gmail.com', 'Jiumo Status', $message);
        // mail('jack@jiumodiary.com', 'Jiumo Status', $message);
        
        
        if ($alive_words_href < 30 ||
            $alive_words_proxy < 1 ||
            $alive_wordseng_proxy < 20 ||
            // $alive_wordskat_proxy < 15 ||
            $alive_wordsbing_proxy < 20 ||
            $alive_wordsishare_proxy < 20 ||
            $alive_wordspkongfz_proxy < 20
            )
        {
            $result = mail('jackjiumo@126.com', 'Error  Jiumo Status', $message);
        }
        else {
            $result = mail('jackjiumo@126.com', 'Normal Jiumo Status', $message);
        }
        
        
        
        
        
    }
    
    
    
    $link = connect_dblink();
    
    
    
    // check whether need update
    $is_newday = is_newday($link);
    if ($is_newday) {
        // start update
        update_lastday_count($link);
        update_search_handler($link);
        update_status_log($link);
        
        update_words_cache($link, 'words');
        update_words_cache($link, 'wordseng');
        // update_words_cache($link, 'wordskat');
        // update_words_cache($link, 'wordsweibo');
        update_words_cache($link, 'wordsbing');
        update_words_cache($link, 'wordspkongfz');
        update_words_cache($link, 'wordsishare');
    }
    
    
    // check whether new minute
    $is_newminute = is_newminute($link);
    if ($is_newminute) {
        
        email_healthy_status($link);
        
        auto_precheck_proxy($link, 'wordseng_proxy');
        auto_fulfill_proxy($link, 'wordseng_proxy');
        
        // auto_precheck_proxy($link, 'wordskat_proxy');
        // auto_fulfill_proxy($link, 'wordskat_proxy');
        
        // auto_precheck_proxy($link, 'wordsweibo_proxy');
        // auto_fulfill_proxy($link, 'wordsweibo_proxy');
        
        auto_precheck_proxy($link, 'wordsbing_proxy');
        auto_fulfill_proxy($link, 'wordsbing_proxy');
        
        auto_precheck_proxy($link, 'wordspkongfz_proxy');
        auto_fulfill_proxy($link, 'wordspkongfz_proxy');
        
        auto_precheck_proxy($link, 'wordsishare_proxy');
        auto_fulfill_proxy($link, 'wordsishare_proxy');
        
        // auto_precheck_proxy($link, 'words_proxy');
        // auto_fulfill_proxy($link, 'words_proxy');
        
        // auto_precheck_proxy($link, 'wordsyahoo_proxy');
        // auto_fulfill_proxy($link, 'wordsyahoo_proxy');
    }
    
    
    
    close_dblink($link);
    
    
    
    
    
    
