<?php
/**
 * TestsAppsController file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestsAppsController
 *
 * @package       Cake.Test.TestApp.Controller
 */
class TestsAppsController extends AppController {

	public $uses = array();

	public array $components = array('RequestHandler');

	public function index() {
		$var = '';
		if (isset($this->request->query['var'])) {
			$var = $this->request->query['var'];
		}
		$this->set('var', $var);
	}

	public function some_method() {
		return 5;
	}

	public function set_action() {
		$this->set('var', 'string');
		$this->render('index');
	}

	public function file() {
		$this->response->file(__FILE__);
	}

	public function redirect_to() {
		return $this->redirect('https://cakephp.org');
	}

}
