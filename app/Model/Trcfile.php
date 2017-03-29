<?php

/**
 * Class Trcfile
 * Trcfile model
 */
class Trcfile extends AppModel
{

    public $hasMany = ['Trcchemical','Dataset'];

    public $belongsTo = ['Reference'];

}