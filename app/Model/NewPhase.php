<?php

/**
 * Class phase
 * Phase model
 */
class NewPhase extends AppModel
{

	public $useDbConfig='new';
	public $useTable='phases';

	public $hasAndBelongsToMany = ['NewMixture','NewPhasetype'];

}
