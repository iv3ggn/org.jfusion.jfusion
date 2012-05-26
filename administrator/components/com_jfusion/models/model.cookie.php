<?php
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class JFusionCookies {
	/**
     * Variable to store cookie data
     */
	var $_cookies = array ();

	public function __construct($secret = null)
	{
		$this->secret = $secret;
	}
	
	/**
     * addCookie
     *
     * @param string $cookie_name
     * @param string $cookie_value
     * @param int $cookie_name
     * @param string $cookiepath
     * @param string $cookiedomain
     * @param int $cookie_secure
     * @param int $cookie_httponly
     */
    function addCookie($cookie_name, $cookie_value='', $cookie_expires_time=0, $cookiepath='', $cookiedomain='', $cookie_secure=0, $cookie_httponly=0) {
    	if ($cookie_expires_time != 0) {
    		$cookie_expires_time = time() + intval($cookie_expires_time);
    	} else {
    		$cookie_expires_time = 0;
    	}
    	
    	// Versions of PHP prior to 5.2 do not support HttpOnly cookies and IE is buggy when specifying a blank domain so set the cookie manually
		$cookie = "{$cookie_name}=".urlencode($cookie_value);
		if ($cookie_expires_time > 0) {
			$cookie .= "; expires=".gmdate('D, d-M-Y H:i:s \\G\\M\\T', $cookie_expires_time);
		}
		if (!empty($cookiepath)) {
			$cookie .= "; path={$cookiepath}";
		}
		
		list ($url,$cookiedomain) = $this->getApiUrl($cookiedomain);
		
		if (!empty($cookiedomain)) {
			$cookie .= "; domain={$cookiedomain}";
		}
		if($cookie_secure == true) {
			$cookie .= '; Secure';
		}
		if ($cookie_httponly == true) {
			$cookie .= "; HttpOnly";
		}
		
		if ($url) {
			$mainframe = & JFactory::getApplication();
			if ( !$mainframe->isAdmin()) {
				$this->_cookies[$url][] = $cookie;
			}
		} else {
			header('Set-Cookie: '.$cookie, false);
		}
    }

    /**
     * Execute the cross domain login redirects
     *
     * @param string $source_url
     * @param string $return
     */
    function executeRedirect($source_url=null,$return=null) {
    	$mainframe = & JFactory::getApplication();
    	if (!$mainframe->isAdmin() || !$this->secret) {
	    	if(!count($this->_cookies)) {
	    		if (empty($return)) {
                    $return = JRequest::getVar ( 'return', '', 'method', 'base64' );
	    			if ($return) {
	    				$return = base64_decode ( $return );
	    				if( stripos($return,'http://') === false && stripos($return,'https://') === false ) {
	    					$return = ltrim($return,'/');
	    					$return = $source_url.$return;
	    				}
	    			}
	    		}
		
				require_once(JPATH_SITE.DS.'components'.DS.'com_jfusion'.DS.'jfusionapi.php');

                $api = null;
                $data = array();
		    	foreach( $this->_cookies as $key => $cookies ) {
		    		$api = new JFusionAPI($key,$this->secret);
		    		if ($api->set('Cookie','Cookies',$cookies)) {
		    			$data['url'][$api->url] = $api->sid;
		    		}
				}
                if ($api) {
                    unset($data['url'][$api->url]);
                    $api->execute('cookie','cookies',$data,$return);
                }
	    	}
	    	if (!empty($return)) {
	    		$mainframe->redirect($return);
	    	}
    	}
    }
    
    public function getApiUrl($cookiedomain) {
    	$url = null;
		if (strpos($cookiedomain,'http://') === 0) {
			$cookiedomain = str_replace('http://', '', $cookiedomain);
			$url = 'http://'.ltrim($cookiedomain,'.');
		} else if (strpos($cookiedomain,'https://') === 0) {
			$cookiedomain = str_replace('https://', '', $cookiedomain);
			$url = 'https://'.ltrim($cookiedomain,'.');
		}
		if ($url) {
			$url = rtrim($url,'/');
			$url = $url.'/jfusionapi.php';
		}
    	return array($url,$cookiedomain);
    }

	/**
	 * Retrieve the cookies as a string cookiename=cookievalue; or as an array
	 *
	 * @param string $type
	 * @return string or array
	 */
	public static function buildCookie($type = 'string'){
		switch ($type) {
			case 'array':
				return $_COOKIE;
				break;
			case 'string':
			default:
				return JFusionCookies::implodeCookies( $_COOKIE, ';' );
			break;
		}
	}

	/**
	 * Can implode an array of any dimension
	 * Uses a few basic rules for implosion:
	 *        1. Replace all instances of delimeters in strings by '/' followed by delimeter
	 *        2. 2 Delimeters in between keys
	 *        3. 3 Delimeters in between key and value
	 *        4. 4 Delimeters in between key-value pairs
	 *
	 * @see model.curl.php and model.curlframeless.php
     * @returns string
	 */
	public static function implodeCookies($array, $delimeter, $keyssofar = '') {
		$output = '';
		foreach ( $array as $key => $value ) {
			if (! is_array ( $value )) {
				if ($keyssofar) $pair = $keyssofar . '[' . $key . ']=' . urlencode($value) . $delimeter;
				else $pair = $key . '=' . urlencode($value) . $delimeter;
				if ($output != '') $output .= ' ';
				$output .= $pair;
			}
			else {
				if ($output != '') $output .= ' ';
				$output .= self::implodeCookies ( $value, $delimeter, $key . $keyssofar );
			}
		}
		return $output;
	}
}
