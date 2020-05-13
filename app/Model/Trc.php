<?php

/**
 * Class Trc
 * Trc model
 */
class Trc extends AppModel
{
	public $useDbConfig = 'crosswalk';
	public $useTable = 'trc';

	public $belongsTo=['Ontterm'];
}
