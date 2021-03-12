<?php

/**
 * Class Reactionprop
 * Reactionprop model
 */
class NewReactionprop extends AppModel
{

	public $useDbConfig='new';
	public $useTable='reactionprops';

	public $belongsTo = [
    	'NewDataset'=> [
			'foreignKey' => 'dataset_id'
		]
	];

}
