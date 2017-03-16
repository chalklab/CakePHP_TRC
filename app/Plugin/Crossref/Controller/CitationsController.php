<?php

/**
 * Class CitationsController for the Crossref
 */
class CitationsController extends CrossrefAppController
{

    // Uses the report and substance models
    public $uses=['Crossref'];

    // Tells CakePHP that this controller is not associated with a database table
    public $usesTable=false;

    /**
     * Get paper by its doi
     * @param $doi
     */
    public function bydoi($doi)
    {
        $data=$this->Crossref->getmeta($doi);
        $this->set('data',$data);
        $this->render('display');
    }
    
}