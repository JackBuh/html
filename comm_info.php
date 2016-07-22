<?php
    
    include_once ('hitcounter/counter.php');
    include_once ('Mobile_Detect.php');
    
    
    
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
    

    
    
    function get_share_count($link, $new_share) {
        
 
        
        // ################################################
        // ######### get share count ###########
        // ################################################
        
        $share_count = 0;
        $result = mysql_query("SELECT count FROM info_record WHERE record='share'", $link);
        $row = mysql_fetch_array($result);
        
        if ($row) {
            $share_count = $row['count'];
            
            if($new_share){
                
                $share_count = $share_count + 1;
                $adddata = mysql_query("UPDATE info_record SET count=$share_count  WHERE record='share'", $link);
            }
        }
        else {
            
            $adddata = mysql_query("INSERT INTO info_record(record, count) VALUES('share' , 0)", $link);
        }
        
        
        return $share_count;
    }
    
    
    
    
    // page info record
    $page = 'resource search';
    addinfo($page);
    
    $link = connect_dblink();
    $share_count = get_share_count($link, false);
    close_dblink($link);
    
 
    $info = array();
    
    $info['country'] = $country;
    $info['share_count'] = $share_count;
    
    // device detection
    $device = 'normal';
    $detect = new Mobile_Detect;
    if( $detect->isMobile() && !$detect->isTablet() ){
        $device = 'mobile';
    }
    if( $detect->isTablet() ){
        $device = 'tablet';
    }
    
    
    $info['device'] = $device;
    
    echo json_encode($info);





