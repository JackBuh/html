<?php
    
    // lagency: google ips management and validating ,
    // lagency: t*e*m*p solution (furing require dynamic checking on serversite with strategies)
    
    include_once ('header_config.php');
    include_once ('hitcounter/counter_config.php');
    
    
    
    function handler_result_sort_lasttime($a, $b) {
        $a_time = $a['lasttime'];
        $b_time = $b['lasttime'];
        
        if ($a_time == $b_time) {
            return 0; 
        }
        return ($a_time < $b_time) ? -1 : 1;
    }
    
    
    function get_search_handler_proxy($link, $db, $target_url) {
        
        
        // current time
        date_default_timezone_set("Asia/Hong_Kong");
        $datetime = date("Y/m/d") . ' ' . date('H:i:s');
        
        $mod_offset = '-' . '1' . ' minute';
        $datetime_mod = date("Y/m/d H:i:s", strtotime($mod_offset));
        
        $mod_offset_precheck = '-' . '30' . ' minute';
        $datetime_mod_precheck = date("Y/m/d H:i:s", strtotime($mod_offset_precheck));
        
        
        $results = array();
        
        
        // pause if site issue suspecious
        $result_active = mysql_query("SELECT id FROM $db WHERE sick=0", $link);
        $count_active = mysql_num_rows($result_active);
        if ($count_active === 0) {
            return $results;
        }
        
        
        
        
        // perform query
        $result_proxy_query_str =  "SELECT * FROM $db WHERE sick>0 AND lasttime<'$datetime_mod' AND (lasttime_precheck<'$datetime_mod_precheck' OR lasttime_precheck IS NULL) ORDER BY lasttime";
        $result_proxy = mysql_query($result_proxy_query_str, $link);
        while ($row_proxy = mysql_fetch_array($result_proxy)) {
            
            $search_handler = array(
                                    "handler" => $db,
                                    "handler_id" => $row_proxy['id'],
                                    "target_url" => $target_url,
                                    "proxy_address" => $row_proxy['address'],
                                    "proxy_port" => $row_proxy['port'],
                                    "proxy_type" => $row_proxy['type'],
                                    "backup_prefix" => '',
                                    "lasttime" => $row_proxy['lasttime'],
                                    );
            
            $id = $row_proxy['id'];
            $adddata = mysql_query("UPDATE $db SET lasttime_precheck='$datetime' WHERE id=$id", $link);
            $results[] = $search_handler;
        }
        
        
        // sort by lasttime
        usort($results, "handler_result_sort_lasttime");
        
        
        return $results;

    }
    
    
    
    function update_search_handler_proxy($link, $search_handler, $status, $log) {

        $handler_table = $search_handler['handler'];
        $id = $search_handler['handler_id'];
       
        
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
                    $mod_offset = '+' . $mod_sick * 30 . ' minute';
                    $datetime_mod = date("Y/m/d", strtotime($mod_offset)) . ' ' . date('H:i:s', strtotime($mod_offset));
                }
                $adddata = mysql_query("UPDATE $handler_table SET total=total+1, total_day=total_day+1, lasttime='$datetime_mod' , sick=$mod_sick, error=error+1, error_day=error_day+1, log='$new_log' WHERE id=$id", $link);
                break;
            
        
            case "pass":
                // pass then add precheck log
                $adddata = mysql_query("UPDATE $handler_table SET log='$new_log' WHERE id=$id", $link);
                break;
                
            default:
                $new_log = 'precheck update escaped error; ' . $new_log;
                $adddata = mysql_query("UPDATE $handler_table SET log='$new_log' WHERE id=$id", $link);
                
        }
        
        
    }
    
    
    
    // start parse result
    function fetch_result_precheck_proxy($search_handler, &$status, &$log) {
        
        $fetched = array();
        $url = $search_handler['target_url'];
        
        
        // create curl resource
        $ch = curl_init();
        
        $proxy = $search_handler['proxy_address'].':'.$search_handler['proxy_port'];
        
            
        curl_setopt($ch, CURLOPT_CONNECTIONTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        
        
        
        // set url
        curl_setopt($ch, CURLOPT_URL, $url);
        
        // just send empty cookie.
        curl_setopt($ch, CURLOPT_COOKIE, '');
        
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        

        
        // user agent
        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36';
        curl_setopt($ch,CURLOPT_USERAGENT, $user_agent);
        
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        

        
        // $output contains the output string
        $output = curl_exec($ch);
        

        
        
        
        if (curl_errno($ch)) {
            $estr = curl_error($ch);
            $status = 'error';
            $log = 'precheck fail ' . $estr;
            return $fetched;
        }
        
        
        // close curl resource to free up system resources
        curl_close($ch);
         
        

        
        $status = 'pass';
        $log = 'precheck pass ';
        return $fetched;
    }
    
    
    
    
    function auto_precheck_proxy($link, $db) {
        
        
        if ( !($db === 'wordseng_proxy' || $db === 'wordskat_proxy' || $db === 'wordsweibo_proxy' || $db === 'wordsbing_proxy' || $db === 'wordsyahoo_proxy'  || $db === 'wordspkongfz_proxy'  || $db === 'wordsishare_proxy') ) {
            return;
        }
        
        $target_url = '';
        if ($db === 'wordseng_proxy') {
            $target_url = "http://" . get_libgen() . "/search.php";
            // $target_url = "http://gen.lib.rus.ec/search.php";
        }
        if ($db === 'wordskat_proxy') {
            $target_url = "https://kat.cr";
        }
        if ($db === 'wordsweibo_proxy') {
            $target_url = "http://vdisk.weibo.com/search/";
        }
        if ($db === 'wordsbing_proxy') {
            $target_url = "https://www.bing.com";
        }
        if ($db === 'wordsyahoo_proxy') {
            $target_url = "https://search.yahoo.com";
        }
        if ($db === 'wordspkongfz_proxy') {
            $target_url = "http://search.kongfz.com";
        }
        if ($db === 'wordsishare_proxy') {
            $target_url = "http://ishare.iask.sina.com.cn";
        }
        
        $status = '';
        $log = '';
        
        $search_handler_array = get_search_handler_proxy($link, $db, $target_url);
        for ($i=0; $i<count($search_handler_array); $i++) {
            
            $fetched = fetch_result_precheck_proxy($search_handler_array[$i], $status, $log);
            update_search_handler_proxy($link, $search_handler_array[$i], $status, $log);
        }
        
    }
    
    
    

