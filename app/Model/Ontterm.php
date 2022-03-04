<?php

/**
 * Class Ontterm
 * model for the ontterms table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Ontterm extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['Nspace'];
}
