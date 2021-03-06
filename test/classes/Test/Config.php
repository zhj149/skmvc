<?php defined('SYSPATH') OR die('No direct script access.');

class Test_Config extends PHPUnit_Framework_TestCase
{
	public function test_load(){
		$config = Config::load('router');
		$this->assertArrayHasKey('/.+/', $config);
		$this->assertInstanceOf('Closure', $config['/.+/']); // 函数
	}
}