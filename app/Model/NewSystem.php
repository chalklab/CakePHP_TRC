<?php

/**
 * Class System
 * System model
 */
class NewSystem extends AppModel
{
	public $useDbConfig='new';
	public $useTable='systems';

	public $hasAndBelongsToMany = ['NewSubstance','NewData','NewFile'];


    public $hasMany = [
    	'NewDataset'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewCondition'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewData'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewSetting'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		],
		'NewMixture'=> [
			'foreignKey' => 'system_id',
			'dependent' => true
		]
	];


    public $virtualFields=['first' => 'UPPER(SUBSTR(NewSystem.name,1,1))'];
}
