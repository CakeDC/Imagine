<?php
declare(strict_types = 1);

/**
 * Copyright 2011-2017, Florian Krämer
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * Copyright 2011-2017, Florian Krämer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Burzum\Imagine\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\ServerRequest as Request;
use Cake\Http\Response;
use Cake\TestSuite\TestCase;

/**
 * ImagineImagesTestController
 */
class ImagineImagesTestController extends Controller {

	/**
	 * @var string
	 */
	public $name = 'Images';

	/**
	 * @var array
	 */
	public $uses = ['Images'];

	/**
	 * @var array
	 */
	public $components = [
		'Burzum/Imagine.Imagine'
	];

	/**
	 * Redirect url
	 * @var mixed
	 */
	public $redirectUrl = null;

	/**
	 *
	 */
	public function beforeFilter(Event $Event) {
		parent::beforeFilter($Event);
		$this->Imagine->userModel = 'UserModel';
	}

	/**
	 *
	 */
	public function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}
}

/**
 * Imagine Component Test
 *
 * @package Imagine
 * @subpackage Imagine.tests.cases.components
 */
class ImagineComponentTest extends TestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Burzum\Imagine.Image'
	];

	/**
	 * Controller
	 *
	 * @var \Cake\Controller\Controller
	 */
	public $Controller;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		Configure::write('Imagine.salt', 'this-is-a-nice-salt');
		$request = new Request();
		$response = new Response();
		$this->Controller = new ImagineImagesTestController($request, $response);
		$this->Controller->Imagine->Controller = $this->Controller;
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Controller);
	}

	/**
	 * testGetHash method
	 *
	 * @return void
	 */
	public function testGetHash() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams([
			'thumbnail' => 'width|200;height|150'
		]));

		$hash = $this->Controller->Imagine->getHash();
		$this->assertTrue(is_string($hash));
	}

	/**
	 * testCheckHash method
	 *
	 * @return void
	 */
	public function testCheckHash() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams([
			'thumbnail' => 'width|200;height|150',
			'hash' => '69aa9f46cdc5a200dc7539fc10eec00f2ba89023'
		]));

		$result = $this->Controller->Imagine->checkHash();
		$this->assertTrue($result);
	}

	/**
	 * @expectedException \Cake\Http\Exception\NotFoundException
	 */
	public function testInvalidHash() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams([
			'thumbnail' => 'width|200;height|150',
			'hash' => 'wrong-hash-value'
		]));

		$this->Controller->Imagine->checkHash();
	}

	/**
	 * @expectedException \Cake\Http\Exception\NotFoundException
	 */
	public function testMissingHash() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams([
			'thumbnail' => 'width|200;height|150'
		]));

		$this->Controller->Imagine->checkHash();
	}

	/**
	 * testCheckHash method
	 *
	 * @return void
	 */
	public function testUnpackParams() {
		$this->Controller->setRequest($this->Controller->getRequest()->withQueryParams([
			'thumbnail' => 'width|200;height|150'
		]));

		$this->assertEquals($this->Controller->Imagine->operations, []);
		//$this->Controller->getRequest()->query['thumbnail'] = 'width|200;height|150';
		$this->Controller->Imagine->unpackParams();
		$this->assertEquals($this->Controller->Imagine->operations, [
				'thumbnail' => [
					'width' => 200,
					'height' => 150
				]
			]);
	}

}
