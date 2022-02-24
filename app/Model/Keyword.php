<?php

/**
 * Class keyword
 * model for the keywords table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Keyword extends AppModel
{
	// relationships to other tables
	public $belongsTo=['Report'];

	// create additional 'virtual' fields built from real fields
	public $virtualFields=['termcnt'=>'count(term)','tfirst'=>'UPPER(LEFT(term, 1))'];
}
