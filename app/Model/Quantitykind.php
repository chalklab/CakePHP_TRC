<?php

/**
 * Class Quantitykinds
 * model for the quantitykinds table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Quantitykind extends AppModel
{
	// relationships to other tables
	public $belongsTo = [
		'Unit'=>['foreignKey' => 'si_unit']
	];

}
