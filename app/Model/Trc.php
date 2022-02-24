<?php

/**
 * Class Trc
 * model for the trc table in the crosswalks DB
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Trc extends AppModel
{
	public $useDbConfig = 'crosswalk';
	public $useTable = 'trc';

	// relationships to other tables
	public $belongsTo=['Ontterm'];
}
