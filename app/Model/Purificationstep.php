<?php

/**
 * Class Purificationstep
 * model for the purificationsteps table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Purificationstep extends AppModel
{
	// create additional 'virtual' fields built from real fields
	public $virtualFields=['chemstep'=>'CONCAT(Purificationstep.chemical_id,":",Purificationstep.step)'];

}
