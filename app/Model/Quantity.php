<?php

/**
 * Class Quantity
 * model for the quantities table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Quantity extends AppModel
{
	// relationships to other tables
	public $belongsTo = ['Quantitykind',
		'Unit'=>['foreignKey' => 'defunit_id']
	];
	public $hasMany = ['Data','Condition','Sampleprop'];

	// create additional 'virtual' fields built from real fields
	public $virtualFields = ['first'=>'SUBSTRING(Quantity.name, 1, 1)'];
}
