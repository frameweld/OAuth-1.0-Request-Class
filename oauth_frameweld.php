<?php
namespace recapd;
/**
 * OAuth 1.0 Request Class
 * 
 * NOTE: This class uses namespacing.  If you current framework doesn't, remove the namespace above and set the
 * $namespace variable to an empty string.
 * 	
 * REQUIREMENT:
 * 
 * PHP 5.3 +
 * @author Omar D. Ellis <omar@frameweld.com>
 * @access public
 * @copyright All Code Copyright 2001 Frameweld, LLC.  All Rights Reserved.
 */
class oauth_frameweld {
	/**
	 * Current version of OAuth being used.
	 * @var string
	 */
	private static $version = '1.0';
	/**
	 * The name space for the project.
	 * @var string
	 */
	private static $namespace = '';
	/**
	 * The path to the Recap'd API Server.
	 * @var string
	 */
	private static $api_server_url = '';
	/**
	 * The API version to use for the current request.
	 * @var string
	 */
	private static $api_version = 'api.frameweld-v1';
	/**
	 * This is the oauth_frameweld object for chaining.
	 * @var object
	 */
	private static $instance = null;
	/**
	 * The cURL options used for requests.
	 * @var array
	 */
	private static $curlOptions = array();
	/**
	 * The request options used when creating the authorization header and sending a
	 * request to the server.
	 * @var array
	 */
	private static $requestOptions = array(
		 'public_key' => '',
		 'private_key' => '', /** This key will be wrapped and hidden.  Make sure you keep this key secured. */
		 'api_url' => '', /** Change this to whatever resource you need. */
		 'method' => 'GET' /** Only POST and GET methods are accepted. */
	);
	/**
	 * The error message sent back from the server.
	 * @var string
	 */
	private static $error_message = null;
	/**
	 * The response returned from the server.
	 * @var array
	 */
	private static $response = array();
	/**
	 * The headers returned after a call is made.
	 * @var array
	 */
	private static $headers = array();
	/**
	 * This method will instantiated the oauth_frameweld class and reset the curl options.  You should only call this method
	 * once per sendRequest.
	 * @access public
	 * @return object
	 */
	public static function init() {
		/**
		 * Remove the below if you are not using namespacing.
		 */
		self::$namespace = '\\'.str_replace('\\','\\',__NAMESPACE__) . '\\';
		
		if(!(self::$instance instanceof oauth_frameweld)) {
			self::$instance = new oauth_frameweld();
		}
		
		if(isset($_SERVER['HTTP_HOST']) && strpos('recapd.com', $_SERVER['HTTP_HOST']) != -1) {
			$prefix = explode('.',$_SERVER['HTTP_HOST']);
			$prefix = ($prefix[0] != 'api') ? $prefix[0] . '.' : '';
		}
		
		self::$api_server_url = 'http://' . $prefix. 'api.recapd.com/';
		
		self::$curlOptions = array(
			 CURLOPT_TIMEOUT => 120,
			 CURLOPT_RETURNTRANSFER => true,
			 CURLINFO_HEADER_OUT => true,
			 CURLOPT_CUSTOMREQUEST => '',
			 CURLOPT_URL => '',
			 CURLOPT_HTTPHEADER => '',
			 CURLOPT_HEADER => true	
		);
		
		return self::$instance;
	}
	/**
	 * This method is used to check if there was an error in the recent request.
	 * @access public
	 * @return boolean
	 */
	public static function isError() {
		return (self::$error_message) ? true : false;	
	}
	/**
	 * This method is used to send a request to the Recap'd server.
	 * @access public
	 * @return boolean
	 */
	public static function sendRequest() {
		if(!isset(self::$requestOptions['api_url'])) {
			trigger_error('sendRequest() - You must specify a URL', E_USER_ERROR);
		}
		if(!isset(self::$requestOptions['public_key'])) {
			trigger_error('sendRequest() - Public has to be set.', E_USER_ERROR);
		}
		if(!isset(self::$requestOptions['private_key'])) {
			trigger_error('sendRequest() - Private has to be set.', E_USER_ERROR);
		}
		
		self::$error_message = '';
		self::$response = '';
		
		$authorization_header = self::__getAuthorizationHeader(self::$requestOptions);
		
		$http_header = array(
			 'Accept:application/json+'.self::$api_version,
			 $authorization_header
		);
		
		self::setCurlOption(
				array(		 
						CURLOPT_URL => self::$api_server_url . ltrim(self::$requestOptions['api_url'], '/'),
		 				CURLOPT_HTTPHEADER => $http_header,
						CURLOPT_CUSTOMREQUEST => self::$requestOptions['method'],
				)
		);

		$ch = curl_init();
		curl_setopt_array($ch, self::$curlOptions);
			
		$data = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			            
		preg_match_all('/^(\S*?): (.*?)$/ms', substr($data, 0, $header_size), $matches);
        $info = [];
        for($i = 0; $i < count($matches[1]); $i++) {
            $info[$matches[1][$i]] = $matches[2][$i];
        }
            
        $headers = (!empty($info)) ? $info : [];
		self::$response['headers'] = $headers;
		$response = substr($data, $header_size);
		self::$response['data'] = json_decode($response, true); /** You will need to decode the data returned. */
		self::$headers = curl_getinfo($ch); /** Not needed required, but if there is an error, it will be available through this method call. */
		curl_close($ch);

		if (self::$headers['http_code'] != 200) {
			self::$error_message = $response;
			return false;
		}
		
		return true;
	}
	/**
	 * This method will update values in the request options.
	 * @param array $options The options to update in the request options.
	 * @access public
	 * @return object
	 */
	public static function setRequestOptions(array $options) {
		foreach($options as $key => $value) {
			if(!isset(self::$requestOptions[$key])) {
				continue;
			}
			self::$requestOptions[$key] = $value;
		}
		return self::$instance;	
	}
	/**
	 * This method will update values in the cURL settings. 
	 * @param array $options The options to update in the cURL settings.
	 * @access public
	 * @return object
	 */
	public static function setCurlOption(array $options) {
		foreach($options as $key => $value) {
			self::$curlOptions[$key] = $value;
		}
		return self::$instance;	
	}
	/**
	 * This method will return the error message if one is available.
	 * @access public
	 * @return string
	 */
	public static function getErrorMessage() {
		return self::$error_message;	
	}
	/**
	 * This method will return the recent response headers from the server.
	 * @access public
	 * @return array
	 */
	public static function getResponseHeaders() {
		return self::$response['headers'];	
	}
	/**
	 * This method will return the recent response data from the server.
	 * @access public
	 * @return array
	 */
	public static function getData() {
		return self::$response['data'];
	}
	/**
	 * This method will return the headers from the previous request.
	 * @access public
	 * @return array
	 */
	public static function getRequestHeaders() {
		return self::$headers;	
	}
	/**
	 * This method will return an authorized header to send to Frameweld's API Server.
	 * Make sure not to expose your private key to the public. 
	 * @param array $options The options to use to package the authorization signature.
	 * @access public
	 * @return string
	 */
	private static function __getAuthorizationHeader(array $options) {
		$options['api_url'] = self::$api_server_url . ltrim($options['api_url'], '/');
					
		$params = array(
				'oauth_version' => self::$version,
				'oauth_nonce' => self::__getNonce(),
				'oauth_timestamp' => time(),
				'oauth_consumer_key' => $options['public_key'],
				'oauth_signature_method' => 'HMAC-SHA1'
		);
	
		$params = array_merge(self::__parseParameters(parse_url($options['api_url'], PHP_URL_QUERY)), $params);
		
		/**
		 * The below will sort the parameters and get signable keys.
		 */
		$keys = self::__urlencodeRfc3986(array_keys($params));
		$values = self::__urlencodeRfc3986(array_values($params));
		$params = array_combine($keys, $values);
	
		$params_temp = $params;
		uksort($params_temp, 'strcmp');
		$pairs = array();
		foreach ($params_temp as $parameter => $value) {
			if (is_array($value)) {
				sort($value, SORT_STRING);
				foreach ($value as $duplicate_value) {
					$pairs[] = $parameter . '=' . $duplicate_value;
				}
			} else {
				$pairs[] = $parameter . '=' . $value;
			}
		}
		$signableParams = implode('&', $pairs);
		/**
		 * The below will get the "normalized" URL and encode it.
		 */
		$parts = parse_url($options['api_url']);
		$scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
		$port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
		$host = (isset($parts['host'])) ? strtolower($parts['host']) : '';
		$path = (isset($parts['path'])) ? $parts['path'] : '';
		if (($scheme == 'https' && $port != '443')
				|| ($scheme == 'http' && $port != '80')) {
			$host = "$host:$port";
		}
	
		$normalizedUrl =  "$scheme://$host$path";
		$parts = array(
						strtoupper($options['method']),
						$normalizedUrl,
						$signableParams
				);
		$parts = self::__urlencodeRfc3986($parts);
		/**
		 * The below will get the ouath signature.
		 */
		$base_string = implode('&', $parts);
		$key_parts = self::__urlencodeRfc3986(array($options['private_key'],''));
		$key = implode('&', $key_parts);
		$params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $key, true));
		/**
		 * The below will tie everything together into the authorization header.
		 */
		$first = true;
		$authorization = 'Authorization: OAuth ';
	
		foreach ($params as $k => $v) {
			if (substr($k, 0, 5) != "oauth") continue;
			if (is_array($v)) {
				trigger_error('getAuthorizationHeader() - Arrays not supported in headers.', E_USER_ERROR);
			}
			$authorization .= ($first) ? ' ' : ',';
			$authorization .= self::__urlencodeRfc3986($k) .
			'="' .
			self::__urlencodeRfc3986($v) .
			'"';
			$first = false;
		}
	
		return $authorization;
	}
	/**
	 * This method will encode the parameter using the internet standards.
	 * @param array|string $input The parameter to encode.
	 * @access private
	 * @return multitype:|mixed|string
	 */
	private static function __urlencodeRfc3986($input) {
		if (is_array($input)) {
			return array_map(array( self::$namespace . 'oauth_frameweld', '__urlencodeRfc3986'), $input);
		} else if (is_scalar($input)) {
			return str_replace(
					'+',
					' ',
					str_replace('%7E', '~', rawurlencode($input))
			);
		} else {
			return '';
		}
	}
	/**
	 * This method will decode a parameter using the internet standards.
	 * @param string $string The parameter to decode.
	 * @access private
	 * @return string
	 */
	private static function __urldecodeRfc3986($string) {
		return urldecode($string);
	}
	/**
	 * This method will get the unique nonce to send to server for a single use.
	 * @access private
	 * @return string
	 */
	private static function __getNonce() {
		$mt = microtime();
		$rand = mt_rand();
		return md5($mt . $rand);
	}
	/**
	 * This method will parse a strin gof parameters and decode the key and the
	 * values.
	 * @param string $input The parameters to parse.
	 * @access private
	 * @return multitype:|multitype:multitype:Ambigous <multitype:>  string
	 */
	private static function __parseParameters($input) {
		if (!isset($input) || !$input) return array();	
		$pairs = explode('&', $input);	
		$parsed_parameters = array();
		foreach ($pairs as $pair) {
			$split = explode('=', $pair, 2);
			$parameter = self::__urldecodeRfc3986($split[0]);
			$value = isset($split[1]) ? self::__urldecodeRfc3986($split[1]) : '';	
			if (isset($parsed_parameters[$parameter])) {
				if (is_scalar($parsed_parameters[$parameter])) {
					$parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
				}
	
				$parsed_parameters[$parameter][] = $value;
			} else {
				$parsed_parameters[$parameter] = $value;
			}
		}
		return $parsed_parameters;
	}	
}
?>