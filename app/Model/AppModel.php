<?php
App::uses('Model', 'Model');
App::uses('ClassRegistry', 'Utility');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

    public $actsAs = ['Containable'];

    function fixUTF8(&$value,$key){
        if(is_string($value)){
            $value=utf8_encode($value);
        }
    }

}