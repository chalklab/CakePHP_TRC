<?php

/**
 * Class DataController
 * model for the table of experimental data
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class DataController extends AppController
{
	public $uses=['Data','Unit'];

	/**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

	/**
	 * view a list of the data
	 * used in initial development, this code will likely cause an out
	 * of memory error as it is trying to list over 2.7 million rows of data
	 * @return void
	 */
	public function index()
	{
		// add a virtualField on the fly - the related table (Unit) must also be added to the 'contain' array
		$this->Data->virtualFields['numunit'] = 'CONCAT(Data.Number," ",Unit.symbol)';
		$f=['Data.id','Data.numunit','Quantity.name'];$c=['Quantity','Unit'];
		$data=$this->Data->find('list', ['fields'=>$f,'contain'=>$c,'order'=>'Quantity.name','limit'=>100,'recursive'=>-1]);
		$this->set('data',$data);
	}

	/**
     * view a datum
	 * not a particularly useful function to see one condition out of context
	 * however it does show how to search for data in related tables...
	 * @param int $did
	 * @return void
	 */
	public function view(int $did)
	{
		$cs=['Data.id'=>$did];$c=['Dataset','Dataseries','Datapoint','Quantity','Sampleprop','Compohnent'=>['Chemical'=>['Substance']],'Phase'=>['Phasetype'],'Unit'];
		$datum=$this->Data->find('first',['conditions'=>$cs,'contain'=>$c,'limit'=>100,'recursive'=>-1]);
		$this->set('datum',$datum);
	}

	// functions requiring login (not in Auth::allow)

	/**
	 * delete a datum
	 * @param int $did
	 * @return void
	 */
	public function delete(int $did)
	{
		$this->Data->delete($did);
		$this->redirect('/');
	}

}
