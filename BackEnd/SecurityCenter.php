<?php
//    static  //self
//    private  //$this->
class SecurityCenter{

    //urlcheck
    private static $urlRegex = "/^(http(?:s)?\\:\\/\\/[a-zA-Z0-9]+(?:(?:\\.|\\-)[a-zA-Z0-9]+)+(?:\\:\\d+)?(?:\\/[\\w\\-]+)*(?:\\/?|\\/\\w+\\.[a-zA-Z]{2,4}(?:\\?[\\w]+\\=[\\w\\-]+)?)?(?:\\&[\\w]+\\=[\\w\\-]+)*)$/";
    //simple user-agent check binary match
    private static $browsers = Array(1 => 'msie',
                2 => 'chrome',
                3 => 'safari',
                4 => 'firefox',
                5 => 'opera'
        );
    //for post, whitelisted static paths e.g /cgi-bin/login.cgi?malicios&stuff not
    private static $allowedQueries = Array(
            1 => '/cgi-bin/login.cgi',
            2 => '/cgi-bin/register.cgi',
            3 => '/cgi-bin/logout.cgi',
            4 => '/cgi-bin/addPost.cgi',
            5 => '/cgi-bin/getPosts.cgi',
            6 => '/cgi-bin/search.cgi',
        );
    //get parameter value filter (empty not allowed)
    private static $queryRegex = "/^[a-zA-Z0-9]+$/";
    
    /**
     * Verify http header from client (covers most used vectors 70%)
     * @return boolean true if header's inject free
     */
    public function requestHeaderCheck(){

        //check for potential inject
        if(isset($_SERVER['HTTP_REFERER'])){
            if((preg_match(self::$urlRegex, ($this->equalString($_SERVER['HTTP_REFERER'])))) !== 1){
                //log
                exit('Referer invalid');
                return false;
            }
        }
        //we use jquery and ajax, therefore this is required
        if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            //log
            exit('not ajax');
            return false;
        
        }
        //we allow whitelisted browsers and are safe from user-agent injects
        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            //log
            exit('no agent');
            return false;
        }else{
            foreach (self::$browsers as $key => $value) {

                if((stripos(strtolower($this->equalString($_SERVER['HTTP_USER_AGENT'])), 
                        $this->equalString(self::$browsers[$key]))) === false){
                    //log
                    continue;
                }else{
                    return true;
                }
            }
            exit('bad browser');
            return false;        
        }

        return true;
    }
    /**
     * 
     * @param type $parameter OPTIONAL, for GET requests. We dont accept empty values, a value must be set
     * @return boolean Paramater values ok and validated
     */
    public function requestMethodCheck($parameter){
        //filter invalid paramater values
        if ($_SERVER['REQUEST_METHOD'] === 'GET'){
               
            if(!((isset($_GET[$parameter])) && strlen($_GET[$parameter]) > 0 && (preg_match(self::$queryRegex, $_GET[$parameter])))){
                return false;
            }else{
                return true;
            }
            //same for posts, but allow whitelisted requests only
        }elseif($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['REQUEST_URI'])){
                    //whitelisted uris only
                foreach (self::$allowedQueries as $key => $value) {
                    
                    if(strcmp($this->equalString(self::$allowedQueries[$key]) , 
                       $this->equalString($_SERVER['REQUEST_URI'])) === 0){
                            //log
                        return true;
                    }
                }
                return false;
        }
    }
    
    /**
     * Check if session is valid (antiHiJack)
     * @return boolean true if is
     */
    public function checkSession(){

        session_start();
        $username =  $_SESSION['UName'];
        $ip = $_SESSION['UIp'];
        $useragent = $_SESSION['UAgent'];

        if($_SESSION['UIp'] == $_SERVER['REMOTE_ADDR'] &&
           $_SESSION['UAgent'] == $_SERVER['HTTP_USER_AGENT'] &&
           $_SESSION['Id'] == session_id()
           ){
            return true;
        }else{
            session_unset();
            $_SESSION=array();
            session_destroy();
            return false;
        }

    }
    
    /**
     * Terminates the SS session from memory
     * @return boolean true if ended
     */
    public function endSession(){
        
        try {
            session_start();
            session_unset();
            $_SESSION=array();
            session_destroy();
        } catch (Exception $ex) {
            exit("Internal error");
        }

        return true;
        
    }
    /**
     * Build the session
     * @param type $uname costum param username
     */
    public function startSession($uname){
        
        session_start();
        session_regenerate_id();
        $_SESSION['Id'] = session_id();
        $_SESSION['UName'] = $uname;
        $_SESSION['UIp'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['UAgent'] = $_SERVER['HTTP_USER_AGENT'];
        session_write_close();
           
    }
    
    /**
     * Checks only the structure of json string
     * @param type $string json encoded string
     * @return true if matches, false if error
     */
    public function jsonCheck($string){
        if(!empty($string) && is_string($string) && !preg_match('/[^,:{}\\[\\]0-9.\\-+Eaeflnr-u \\n\\r\\t]/',
        preg_replace('/"(\\.|[^"\\\\])*"/', '', $string))){
            return true;
        }
        return false;
    }
    
    /**
     * We validate both, keys and values
     * @param type $arr array to validate
     * @return boolean true if array matches regex
     */
    public function checkArray($arr, $strickt){
        
        $filterdReverse = Array();
        $filterdArray = Array();
        
        $filterdReverse = preg_grep("/^[a-zA-Z0-9]+$/", array_keys($arr), PREG_GREP_INVERT);
        
        if(!empty($filterdReverse)){
            foreach ($filterdReverse as $value => $key) {      
                $filterdReverse[$value];
                //send to log
            }
        }
        if(!$strickt){
            
            $filterdArray = preg_grep("/^[\s*a-zA-Z0-9,\s\-\?\!\@\.+\s]+$/", $arr, PREG_GREP_INVERT);
            
        }else{
            
            $filterdArray = preg_grep("/^[a-zA-Z0-9]+$/", $arr, PREG_GREP_INVERT);
            
        }
        if(!empty($filterdArray)){
            foreach ($filterdArray as $key => $value) {      
                $filterdArray[$key];
                //send to log
            }
        }

        
        //PREG_GREP_INVERT keep the invalid array elements, no invalid find set true
        if(empty($filterdReverse) && empty($filterdArray)){
            
            return true;
        }else{
            return false;
        }
        return false;
    }
    /**
     * Converts string to the same mapping
     * @param type $string
     * @return type utf8 striung
     */
    public function equalString($string){
        
        return trim(mb_convert_encoding($string, "ISO-8859-1", "UTF-8"));
    }
    
    
}

?>