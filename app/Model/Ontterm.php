<?php

/**
 * Class Ontterm
 * model for the ontterms table in the crosswalks DB
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Ontterm extends AppModel
{
	public $useDbConfig = 'crosswalk';
	public $useTable = 'ontterms';

	// relationships to other tables
	public $belongsTo = ['Nspace'];
}
