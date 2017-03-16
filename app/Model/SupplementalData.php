<?php

/**
 * Class SupplementalData
 * SupplementalData model
 */
class SupplementalData extends AppModel
{

    public $belongsTo = ['Unit','Datapoint','Property','Metadata'];

}