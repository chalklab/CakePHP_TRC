<?php
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * Class ReferencesController
 * Actions related to reports
 * @author Stuart Chalk schalk@unf.edu
 */
class ReferencesController extends AppController
{

	# defining what data models are accessed in this class
	public $uses = ['Reference'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow();
	}

	/**
	 * view a list of references
	 * filter options in the page
	 */
	public function index()
	{
		$f = ['id','title','year']; $o =['year'=>'desc','title'];
		$data = $this->Reference->find('list',['fields'=>$f, 'order'=>$o, 'recursive'=>-1]);
		$this->set('data',$data);
	}

    /**
     * view an entry for a reference
     * @param int $id
	 */
	public function view(int $id)
    {
    	$c = ['Dataset'];
        $data = $this->Reference->find('first', ['conditions'=>['Reference.id'=>$id], 'contain'=>$c, 'recursive'=>-1]);
        $this->set('data',$data);
    }

}
