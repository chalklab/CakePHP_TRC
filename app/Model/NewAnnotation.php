<?php

/**
 * Class Annotation
 * Annotation model
 */
class NewAnnotation extends AppModel
{

	public $useDbConfig='new';
	public $useTable='annotations';

	public $belongsTo = ['NewDataset','NewDataseries','NewDatapoint','NewReport','NewSystem'];

}
