<?php

// bolt
namespace bolt;
use \b;


use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;


// helpers
class helpers {

    static function getRecursiveDirectory($dir, $filesOnly=false, $hidden=false) {
        $items = array();

        // add our files
        foreach (new RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $item) {
            if ($filesOnly AND !$item->isFile()) {continue;}
            if ($hidden === false AND substr($item->getFilename(), 0, 1) == '.' ) {continue;}
            $items[] = $item;
        }
        return $items;

    }

    static function parseStringArguments($str) {
        $args = array();
        $str = trim(str_replace("'", '"', $str)).' ';
        if (preg_match_all('#([a-zA-Z0-9\_]+)\=\"([^\"]+)"\s+#', $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $args[$match[1]] = trim($match[2]);
            }
        }
        return $args;
    }

    static function buildUrl($parts, $start=false) {
        if (!is_array($parts)) {$parts = array(); }

        $base = new \Net_URL2($start);

        // parts
        foreach ($parts as $k => $v) {
            if ($k == 'user') {
                $base->setUserinfo($v, $base->getPassword());
            }
            else if ($k == 'pass') {
                $base->setUserinfo($base->getUser(), $v);
            }
            else if ($k == 'query') {
                $base->setQueryVariables($v);
            }
            else {
                $m = 'set'.ucfirst($k);
                if (method_exists($base, $m)) {
                    call_user_func(array($base, $m), $v);
                }
            }
        }

        return $base->getURL();

    }


    public function path() {
        $path = array();
        foreach (func_get_args() as $part) {
            $v = trim($part,"/");
            if (!empty($v)) {
                $path[] = $v;
            }
        }
        $path = implode(str_replace('/./','/',$path), "/");

        return (substr($path,0,7) == 'phar://' ? $path : "/{$path}");
    }

    public function getDefinedSubClasses($parent) {
        $classes = array();
        foreach (get_declared_classes() as $class) {
            if (empty($class) OR !is_string($class) OR !preg_match_all('#[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*#i', $class)) {return;}
            $c = new \ReflectionClass($class);
            if ($c->isSubclassOf($parent)) {
                $classes[] = $c;
            }
        }
        return $classes;
    }

    // implemnets
    public function isInterfaceOf($obj, $interface) {
        return (is_object($obj) AND ($implements = class_implements($obj)) !== false AND in_array(ltrim($interface,'\\'), $implements));
    }

    public function tokenize($str, $tokens) {
        $keys = array_map(function($val) { return '{'.$val.'}'; }, array_keys($tokens));
        $values = array_values($tokens);
        return str_replace($keys, $values, $str);
    }

    ////////////////////////////////////////////////
    /// payload
    ////////////////////////////////////////////////
    public function payload($payload) {
        if (is_string($payload) AND $payload{0} == ':') {
            $sig = substr($payload, 1, 32);
            $data = substr($payload, 33);
            return ($sig == b::md5($data) ? json_decode(base64_decode($data), true) : false);
        }
        else {
            $json = base64_encode(json_encode($payload));
            return ":".b::md5($json).$json;
        }
    }

    ////////////////////////////////////////////////
    /// encrypt a password
    ////////////////////////////////////////////////
    public function crypt($str, $salt=false) {
        if (!$salt) { $salt = b::config()->salt->value; }
        return crypt( $str, ($salt{0} == '$' ? $salt : '$5$rounds=5000$'.$salt.'$'));
    }

    ////////////////////////////////////////////////
    /// encrypt using mcrypt
    ////////////////////////////////////////////////
    public function encrypt($str, $salt) {
        return self::mcrypt($str, $salt, MCRYPT_ENCRYPT);
    }

    ////////////////////////////////////////////////
    /// deencrypt using mcrypt
    ////////////////////////////////////////////////
    public function decrypt($str, $salt) {
        return self::mcrypt($str, $salt, MCRYPT_DECRYPT);
    }

    ////////////////////////////////////////////////
    /// mcrypt
    ////////////////////////////////////////////////
    public function mcrypt($str, $salt=false, $what=MCRYPT_DECRYPT) {

        // nothing to do
        if (!$str) { return ""; }

        // salt not string it's what
        if (!is_string($salt)) {
            $what = $salt;
        }

        // no salt
        if ( ( $salt === false OR !is_string($salt) ) AND b::_("_domain") ) {
            $salt = b::_("_domain")->salt;
        }

        // encrypt
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');

        // figure our how long our key should be
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
        $ks = mcrypt_enc_get_key_size($td);

        // make our key
        $key = substr(md5($salt), 0, $ks);
        mcrypt_generic_init($td, $key, $iv);

        // do what
        if ( $what == MCRYPT_DECRYPT ) {
            $data = trim(mdecrypt_generic($td, base64_decode($str)));
        }
        else {
            $data = base64_encode(mcrypt_generic($td, $str));
        }

        // end it
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        // return data
        return $data;

    }

    ////////////////////////////////////////////////
    /// @brief generate a random string
    ///
    /// @param $len lenth of string
    ///
    /// @return random string
    ////////////////////////////////////////////////
    public function randString($len=30) {
        // chars
        $chars = array(
                'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
                'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','V','T','V','U','V','W','X','Y','Z',
                '1','2','3','4','5','6','7','8','9','0'
        );

        // suffle
        shuffle($chars);

        // string
        $str = '';

        // do it
        for ( $i = 0; $i < $len; $i++ ) {
                $str .= $chars[array_rand($chars)];
        }

        return $str;

    }
            // shortcut
            public function randomString($len=30) {
                return self::randString($len);
            }


    public function md5($str, $salt=false) {
        $salt = b::config()->get('salt', ($salt ?: '#()e2jd28909u32r09i1e3ji2d8u*(OUD#20kd39dujiakd'))->value;
        return md5($salt.$str.strrev($salt));
    }


    public function uuid($string=false) {
        if (function_exists('uuid_create')) {
            $uuid = uuid_create();
        }
        else {
            $uuid = trim(`uuid`);
        }
        return ($string ? str_replace('-', '', $uuid) : $uuid);
    }

    public function utctime() {
        $dt = new \DateTime('now',new \DateTimeZone('UTC'));
        return $dt->getTimestamp();
    }

    public function convertTimestamp($ts, $to, $from='UTC') {

        // datetime
        $dt = new \DateTime(null, new \DateTimeZone($from));

        // ts
        $dt->setTimestamp($ts);

        // set the timezone
        $dt->setTimezone(new \DateTimeZone($to));

        // give it
        return $dt->format('U');

    }

    public function plural($str, $count, $multi=false) {
        if ( is_array($count) ) { $count = count($count); }

        if ( $multi !== false ) {
            return ( $count!=1 ? $multi : $str );
        }

        if ( substr($str,-1) == 'y' AND $count > 1 ) {
            return substr($str,0,-1)."ies";
        }
        return $str . ($count!=1?'s':'');
    }

    public function possesive($str) {
        if ( strtolower($str) == 'you' ) { return $str{0}.'our'; }
        return $str . (substr($str,-1)=='s'?"'":"'s");
    }

    public function niceDate($ts) {
        $diff = b::utctime() - $ts;
        if ($diff < b::SecondsInYear) {
            return "Today";
        }
        else if ($diff < (b::SecondsInYear*2)) {
            return "Yesterday";
        }
        else if ($diff < (b::SecondsInYear*7)) {
            return date("l", $ts);
        }
        else {
            return date("l, F d, Y");
        }
    }

    public function ago($tm, $short=false) {

        $cur_tm = b::utctime();

         $dif = $cur_tm-$tm;

       // just now
       if ($dif == 0) {
           return 'just now';
       }

        if ($short) {
            $pds = array('s','m','h','d','w');
            if ($dif > (60*60*24*14)) {
               return date('m/d');
            }
        }
        else {
            $pds = array('second','minute','hour','day','week','month','year','decade');
        }

        $lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
        for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);

        $no = floor($no);

        // numbers
        $num = array('zero','one','two','three','four','five','six','seven','eight','nine','ten');

       if($short == false) {
           ($no > 1 ? $pds[$v] .='s' : false);
           if ($no <= 10) {
               $no = $num[$no];
           }
           $x=sprintf("%s %s ", (string)$no,$pds[$v]);
        }
        else {
           $x=sprintf("%d%s ",$no,$pds[$v]);
        }
        return ($short ? trim($x) : trim($x) . ' ago');
    }

    public function left($theTime, $level="days+hours+min+sec") {
            $now = strtotime("now");
            $timeLeft = $theTime - $now;
            $theText = '';

            // splut
            $levels = explode("+",$level);

            if($timeLeft > 0)
            {
            $days = floor($timeLeft/60/60/24);
            $hours = $timeLeft/60/60%24;
            $mins = $timeLeft/60%60;
            $secs = $timeLeft%60;

            // check for days
            if(in_array('days',$levels) AND $days > 0) {
                $theText .= $days . " day";
                if ($days > 1) { $theText .= 's'; }
            }


            if ( in_array('hours',$levels) AND $hours > 0 ) {
                $theText .= ' '.$hours . " hour";
                if ($hours > 1) { $theText .= 's'; }
            }

            if (in_array('min',$levels)) {
                $theText .= ' '.$mins . " min";
                if ($mins > 1) { $theText .= 's'; }
            }

            if(in_array('sec',$levels)) {
                $theText .= ' '.$secs . " sec";
                if ($secs > 1) { $theText .= 's'; }
            }


        }

        return $theText;

    }

    public function short($str, $len=200, $onwords=true, $append=false) {
        if ( mb_strlen($str) < $len ) { return $str; }
        if ( !$onwords ) {
            if ( mb_strlen($str) > $len ) {
                return substr($str,0,$len)."...".$append;
            }
        }
        else {
            $words = explode(' ',$str);
            $final = array();
            $c = 0;
            foreach ( $words as $word ) {
                if ( $c+mb_strlen($word) > $len ) {
                    return implode(' ',$final). '...'.$append;
                }
                $c += mb_strlen($word);
                $final[] = $word;
            }
        }

        return $str;

    }

    public function br2nl($string){
        $return=eregi_replace('<br[[:space:]]*/?'.
        '[[:space:]]*>',chr(13).chr(10),$string);
        return $return;
    }

    public function convertBase($str,$to=36,$from=10) {
        return (string)base_convert(hexdec($str),$from, $to);
    }


    public function mergeArray($a1, $a2) {
       if (!is_array($a1)) { $a1 = array(); }
       if (!is_array($a2)) { $a2 = array(); }

        foreach ( $a2 as $k => $v ) {
            if ( array_key_exists($k, $a1) AND is_array($v) ) {
                $a1[$k] = self::mergeArray($a1[$k], $a2[$k]);
            }
            else {
                $a1[$k] = $v;
            }
        }
        return $a1;
    }

    public function jsonPretty($json) {
        if (!is_string($json)) {$json = json_encode($json);}
        $json = stripslashes($json);

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;


    }

}