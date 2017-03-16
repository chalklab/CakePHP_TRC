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
        ]
    ];

    public $belongsTo = ['Propertytype','System','Reference','File','Report','TextFile'];

    /**
     * Create qudt unit
     * @param $unit
     * @return string
     */
    public function qudt($unit) {
        if($unit=="MHz") {
            $unit="MegaHertz";
        } elseif($unit=="s") {
            $unit="Second";
        } elseif($unit=="Hz") {
            $unit="Hertz";
        } elseif($unit=="nm") {
            $unit="Nanometer";
        } elseif($unit=="°C"||$unit=="&deg;C") {
            $unit="DegreeCelcius";
        }  elseif($unit=="mm<sup>2</sup> s<sup>-1</sup>") {
            $unit="CentiStokes";
        } elseif($unit=="Pa s") {
            $unit="Poiseuille";

        }
        return "qudt:".$unit;
    }

}