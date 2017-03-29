<?php

/**
 * Class Trcchemical
 * Trcchemical model
 */
class Trcchemical extends AppModel
{

    public $belongsTo = ['Trcfile',
                            'Substance',
                            'Unit'=>[
                                'className' => 'Unit',
                                'foreignKey' => 'purityunit_id'
        ]
    ];

}