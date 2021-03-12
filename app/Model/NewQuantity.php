<?php

/**
 * Class Quantity
 * Quantity model
 */
class NewQuantity extends AppModel
{

    public $hasAndBelongsToMany = ['NewUnit'];

}
