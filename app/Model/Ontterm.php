<?php

/**
 * Class ontterm
 * Ontterm model
 */
class Ontterm extends AppModel
{
	public $useDbConfig = 'crosswalk';
	public $useTable = 'ontterms';

	public $belongsTo=['Nspace'];
}
