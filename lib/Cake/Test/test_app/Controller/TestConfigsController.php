<?php

App::uses('CakeErrorController', 'Controller');

class TestConfigsController extends CakeErrorController {

	public array $components = array(
		'RequestHandler' => array(
			'some' => 'config'
		)
	);

}
