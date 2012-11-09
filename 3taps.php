<?php
class threeTapsClient {
	public static $clients = array();

	public $authToken;
	public $host = '3taps.net';
	public $response;
	public $debug = false; // If set to true, will print out requests to error_log()

	
	public static function register($type, $client) {
		self::$clients[$type] = $client;
	}

	public function __construct($authToken) {
		$this->authToken = $authToken;
		
		foreach (self::$clients as $type => $client) {
			$this->$type = new $client($this);
		}
	}
	
	public function request($path, $method, $getParams = array(), $postParams = array()) {
		$url = $path . $method;

		if (!empty($getParams) || (empty($getParams) && empty($postParams))) {
			$getParams['authToken'] = $this->authToken;
			$url .= '?' . http_build_query($getParams);
		}
		
		if (!empty($postParams)) {
			$postParams['authToken'] = $this->authToken;
			$post = http_build_query($postParams);
		} else {
			$post = null;
		}

		$socket = fsockopen($this->host, 80);
		
		if (!$socket) {
			return false;
		}

		$write = (!empty($post) ? 'POST ' : 'GET ') . $url . ' HTTP/1.1' . "\r\n";
		$write .= 'Host: ' . $this->host . "\r\n";
		$write .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
		if (!empty($post)) $write .= 'Content-Length: ' . strlen($post) . "\r\n";
		$write .= 'Connection: close' . "\r\n\r\n";
		if (!empty($post)) $write .= $post . "\r\n\r\n";
		
		if ($this->debug) error_log($write);

		fwrite($socket, $write);
		$chunkedResponseString = '';
		
		while (!feof($socket)) {
			$string = fread($socket, 1024);
			$chunkedResponseString .= $string;
		}

		$chunkedResponseString = substr($chunkedResponseString, strpos($chunkedResponseString, "\r\n\r\n") + 4);
		$responseString = '';
		$chars = 0;
		
		while ($chars < strlen($chunkedResponseString)) {
			$pos = strpos(substr($chunkedResponseString, $chars), "\r\n");
			
			if ($pos > -1) {
				$rawnum = substr($chunkedResponseString, $chars, $pos + 2);
				$num = hexdec(trim($rawnum));
				$chars += strlen($rawnum);
				$chunk = substr($chunkedResponseString, $chars, $num);
			} else {
				$chunk = $chunkedResponseString;
			}
			
			$responseString .= $chunk;
			$chars += strlen($chunk);
		}
		
		if ($this->debug) error_log($responseString);

		if (strpos($responseString, '503 Service Temporarily Unavailable') > 0) {
			return false;
		}

		$json = $responseString;
		
		if (!empty($options['strip_whitespace'])) {
			$json = str_replace(array("\r", "\n"), '', $json);
		}
		
		$json = trim($json);
		$this->response = json_decode($json, true);
		
		if (empty($this->response)) {
			$this->response = self::json_decode($json);
		}
		
		return $this->response;
	}

	private static function json_decode($json) {
		$comment = false;
		$out = '$x=';
	  
		for ($i=0; $i<strlen($json); $i++) {
			if (!$comment) {
				if (($json[$i] == '{') || ($json[$i] == '[')) $out .= ' array(';
				else if (($json[$i] == '}') || ($json[$i] == ']')) $out .= ')';
				else if ($json[$i] == ':') $out .= '=>';
				else $out .= $json[$i];          
			}
			else $out .= $json[$i];
			if ($json[$i] == '"' && $json[($i-1)]!="\\") $comment = !$comment;
		}
		
		@eval($out . ';');
		return isset($x) ? $x : null;
	}
}

class threeTapsReferenceClient {
	public $client;
	public $path = '/reference/';
	
	public function __construct($authToken) {
		if (is_a($authToken, 'threeTapsClient')) {
			$this->client = $authToken;
		} else {
			$this->client = new threeTapsClient($authToken);
		}
	}
	
	public function categories() {
		$response = $this->client->request($this->path, 'categories', null, null);
		
		if (!empty($category_id) && !empty($response)) {
			return $response[0];
		}
		
		return $response;
	}
}

threeTapsClient::register('reference', 'threeTapsReferenceClient');

class threeTapsSearchClient {
	public $client;
	public $path = '/search/';
	
	public function __construct($authToken) {
		if (is_a($authToken, 'threeTapsClient')) {
			$this->client = $authToken;
		} else {
			$this->client = new threeTapsClient($authToken);
		}
	}

	public function count($params) {
		return $this->client->request($this->path, 'count', $params, null);
	}
	
	public function search($params) {
		return $this->client->request($this->path, '', $params, null);
	}
	
	public function summary($params) {
		return $this->client->request($this->path, 'summary', $params, null);
	}
}

threeTapsClient::register('search', 'threeTapsSearchClient');

