<?php

/**
 * Class SuppParameter
 * SuppParameter model
 */
class SuppParameter extends AppModel
{

    public $belongsTo = ['Propertytype','Unit','Property'];

}