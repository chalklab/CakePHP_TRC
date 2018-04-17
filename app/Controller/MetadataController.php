<?php

/**
 * Class MetadataController
 * Controller for Metadata
 */
class MetadataController extends AppController
{
    public $uses = ['Metadata'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

}