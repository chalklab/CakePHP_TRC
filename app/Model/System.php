<?php

/**
 * Class System
 * System model
 */
class System extends AppModel
{
    // Links to special join table directly between data and systems
    public $hasAndBelongsToMany = ['Substance','Data'];

    public $hasMany = ['Dataset'];

    public $virtualFields=['first' => 'UPPER(SUBSTR(System.name,1,1))'];
}