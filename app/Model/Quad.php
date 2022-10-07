<?php

/**
 * Class Purificationstep
 * model for the purificationsteps table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Quad extends AppModel
{
	// create additional 'virtual' fields built from real fields
	public $useDbConfig = 'sciflow';
	public $useTable = 'quads_005';

}
