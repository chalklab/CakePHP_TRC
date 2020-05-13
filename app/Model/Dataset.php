<?php

/**
 * Class Dataset
 * Parameter model
 */
class Dataset extends AppModel
{

    // Removed datapoint from hasMany 7/2/16 SJC (no data in table)
    // Added datasystem as it is a useful table for aggregating data
    public $hasMany = [
        'Dataseries'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'DataSystem'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Annotation'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Sampleprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Reactionprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Chemical'=> [
            'foreignKey'=> 'orgnum','source',
            'dependent' => true
        ]
    ];

    public $belongsTo = ['System','Reference','File'];

    /**
     * Create qudt unit
     * @param $unit
     * @return string
     */
    public function qudt($unit) {
        if($unit=="MHz") {
            $unit="qudt:MegaHz";
        } elseif($unit=="s") {
            $unit="qudt:SEC";
        } elseif($unit=="Hz") {
            $unit="qudt:Hz";
        } elseif($unit=="nm") {
            $unit="qudt:NanoM";
        } elseif($unit=="°C"||$unit=="&deg;C") {
            $unit="qudt:DegC";
        }  elseif($unit=="mm<sup>2</sup> s<sup>-1</sup>") {
            $unit="qudt:CentiSTOKES";
        } elseif($unit=="Pa s") {
            $unit="qudt:POISEUILLE";
        }
        return $unit;
    }

}