<?php

/**
 * Class Publication
 * Reference model
 */
class Publication extends AppModel
{

    // Marked as dependent to make them easy to clean
    public $hasMany = [
        'File'=> [
            'foreignKey' => 'publication_id',
            'dependent' => true
        ],
        'Report'=> [
            'foreignKey' => 'publication_id',
            'dependent' => true
        ]
    ];
    
    public $virtualFields=['citation'=>'CONCAT("\'",Publication.series," - ",Publication.title," (",Publication.volume,")\', ",Publication.authors,", ",Publication.year," ISBN: ",Publication.isbn)'];

}