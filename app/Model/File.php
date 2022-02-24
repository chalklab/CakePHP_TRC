<?php

/**
 * Class File
 * model for the files table
 * @author Stuart Chalk <schalk@unf.edu>
 */
class File extends AppModel
{
	// relationships to other tables
	// chemicals and datasets tables marked as dependent, so they get deleted when a file does
	public $hasMany = [
        'Chemical'=> [
            'foreignKey' => 'file_id',
            'dependent' => true],
        'Dataset'=> [
		    'foreignKey' => 'file_id',
		    'dependent' => true]
    ];
    public $belongsTo = ['Reference'];

	/**
	 * function to add a new file if it does not already exist
	 * @param array $data
	 * @return integer
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('File',$data);
	}
}
