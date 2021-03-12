<?php

/**
 * Class Chemical
 * Chemical model
 */
class Chemical extends AppModel
{

    public $belongsTo = ['File','Substance'];

}
