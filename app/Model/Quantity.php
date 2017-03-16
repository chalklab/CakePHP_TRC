<?php

/**
 * Class Quantity
 * Quantity model
 */
class Quantity extends AppModel
{

    public $hasAndBelongsToMany = ['Unit'];

}