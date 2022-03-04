<?php

/**
 * Class Crosswalk
 * model for the crosswalks table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Crosswalk extends AppModel
{

	// relationships to other tables
	public $belongsTo=['Ontterm'];
}
