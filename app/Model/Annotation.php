<?php

/**
 * Class Annotation
 * Annotation model
 */
class Annotation extends AppModel
{

    public $belongsTo = ['Dataset','Dataseries','Datapoint','Report','System'];

}