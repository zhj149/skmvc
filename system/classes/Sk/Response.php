<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * 响应对象
 * 	TODO: 支持响应文件
 * 
 * @Package package_name 
 * @category 
 * @author shijianhang
 * @date 2016-10-7 下午11:32:07 
 *
 */
class Sk_Response{
	
	// http状态码及其消息
	public static $messages = array(
		// 信息性状态码 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
	
		// 成功状态码 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
	
		// 重定向状态码 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
	
		// 客户端错误状态码 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
	
		// 服务端错误状态码 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);
	
	/**
	 * 响应状态码
	 * @var  integer
	*/
	protected $_status = 200;
	
	/**
	 * http协议
	 * @var string
	 */
	protected $_protocol = 'HTTP/1.1';
	
	/**
	 * 响应头部
	 * @var array
	 */
	protected $_headers = array();
	
	/**
	 * 响应主体
	 * @var string
	 */
	protected $_body = '';
	
	/**
	 * cookies
	 * @var array
	 */
	protected $_cookies = array();
	
	/**
	 * 获得与设置响应主体
	 * 
	 * @param string $content
	 * @return string|Sk_Response
	 */
	public function body($content = NULL)
	{
		//getter
		if ($content === NULL)
			return $this->_body;
	
		//setter
		$this->_body = (string) $content;
		return $this;
	}
	
	/**
	 * 获得与设置http协议
	 * 
	 * @param string $protocol 协议
	 * @return Sk_Response|string
	 */
	public function protocol($protocol = NULL)
	{
		//getter
		if ($protocol === NULL)
			return $this->_protocol;
		
		//setter
		$this->_protocol = strtoupper($protocol);
		return $this;
	}
	
	/**
	 * 读取与设置响应状态码
	 * 
	 * @param string $status 状态码
	 * @return number|Sk_Response
	 */
	public function status($status = NULL)
	{
		// getter
		if ($status === NULL)
			return $this->_status;
		
		// setter
		if(!isset(static::$messages[$status]))
			throw new Exception("无效响应状态码");
		
		$this->_status = (int) $status;
		return $this;
	}
	
	/**
	 * 读取与设置全部头部字段
	 *
	 *       // 获得全部头部字段
	 *       $headers = $response->headers();
	 *
	 *       // 设置头部
	 *       $response->headers(array('Content-Type' => 'text/html', 'Cache-Control' => 'no-cache'));
	 *
	 * @param array $headers 头部字段 
	 * @return mixed
	 */
	public function headers(array $headers = NULL, $merge = TRUE)
	{
		// getter
		if ($headers === NULL)
			return $this->_headers;
		
		// setter
		$this->_headers = $merge ? merge_array($this->_headers, $headers) : $headers;
		return $this;
	}
	
	/**
	 * 获得与设置单个头部字段
	 * 
	 *       // 获得一个头部字段
	 *       $accept = $response->header('Content-Type');
	 *
	 *       // 设置一个头部字段
	 *       $response->header('Content-Type', 'text/html');
	 * 
	 * @param string $key 字段名
	 * @param string $value 字段值
	 * @return string|Sk_Response
	 */
	public function header($key, $value = NULL)
	{
		// getter
		if ($value === NULL)
			return Arr::get($this->_headers, $key);
		
		// setter
		$this->_headers[$key] = $value;
		return $this;
	}
	
	/**
	 * 设置响应缓存
	 *
	 * @param int|string $expires 过期时间
	 * @return Response
	 */
	public function cache($expires = FALSE) {
		if ($expires === FALSE) {
			$this->headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
			$this->headers['Cache-Control'] = array(
					'no-store, no-cache, must-revalidate',
					'post-check=0, pre-check=0',
					'max-age=0'
			);
			$this->headers['Pragma'] = 'no-cache';
		}
		else 
		{
			$expires = is_int($expires) ? $expires : strtotime($expires);
			$this->headers['Expires'] = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
			$this->headers['Cache-Control'] = 'max-age='.($expires - time());
			if (isset($this->headers['Pragma']) && $this->headers['Pragma'] == 'no-cache')
				unset($this->headers['Pragma']);
		}
		return $this;
	}
	
	/**
	 * 发送头部给客户端
	 * @return Response
	 */
	public function send_headers()
	{
		if(headers_sent())
			return;
		
		// 1 状态行
		header($this->_protocol.' '.$this->_status.' '.static::$messages[$this->_status]);
	
		// 2 各个头部字段
		foreach ($this->_headers as $header => $value)
		{
			// cookie字段
			if($key == 'Set-Cookie')
			{
				Cookie::set($value);
				continue;
			}
			
			// 其他字段
			if (is_array($value)) // 多值拼接
				$value = implode(', ', $value);
		
			header(Text::ucfirst($header).': '.$value, TRUE);
		}
		
		// 正文大小
		if (($length = strlen($this->_body)) > 0) {
			header('Content-Length: '.$length);
		}
	
		return $this;
	}
	
	/**
	 * 发送响应该客户端
	 */
	public function send()
	{
		// 清空内容缓存
		if (ob_get_length() > 0)
			ob_end_clean();
		
		// 先头部，后主体
		echo $this->send_headers()->body();
		
		// 输出内容缓存
		$this->flush();
	}
	
	/**
	 * 将响应内容刷到客户端
	 */
	public function flush()
	{
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif ('cli' !== PHP_SAPI) {
			// ob_get_level() never returns 0 on some Windows configurations, so if
			// the level is the same two times in a row, the loop should be stopped.
			$previous = null;
			$obStatus = ob_get_status(1);
			while (($level = ob_get_level()) > 0 && $level !== $previous) {
				$previous = $level;
				if ($obStatus[$level - 1] && isset($obStatus[$level - 1]['del']) && $obStatus[$level - 1]['del']) {
					ob_end_flush();
				}
			}
			flush();
		}
	}
}