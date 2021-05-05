<?php

/**
 * Class Quantity
 * Quantity model
 */
class NewQuantity extends AppModel
{
	public $useDbConfig='new';
	public $useTable='quantities';

    public $hasAndBelongsToMany = ['NewUnit'];

}
