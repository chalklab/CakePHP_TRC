<?php

/**
 * Class Setting
 * Setting model
 */
class Setting extends AppModel
{

    public $belongsTo = ['Dataset','Dataseries','Unit','Datapoint','Property','Data'];

}