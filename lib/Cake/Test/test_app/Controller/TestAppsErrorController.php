<?php

App::uses('CakeErrorController', 'Controller');

class TestAppsErrorController extends CakeErrorController {

	public array $helpers = array(
		'Html',
		'Session',
		'Form',
		'Banana',
	);

}
