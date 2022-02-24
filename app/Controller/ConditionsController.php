<?php

/**
 * Class ConditionsController
 * controller for the experimental conditions table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class ConditionsController extends AppController
{
    public $uses=['Condition','Unit'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

    /**
     * list of the conditions
	 * used in initial development, this code will likely cause an out
	 * of memory error as it is trying to list over 3.5 million rows of conditions
	 * @return void
     */
    public function index()
	{
		// add a virtualField on the fly - the related table (Unit) must also be added to the 'contain' array
		$this->Condition->virtualFields['numunit'] = 'CONCAT(Condition.Number," ",Unit.symbol)';
		$f=['Condition.id','Condition.numunit','Quantity.name'];$c=['Quantity','Unit'];
        $conds=$this->Condition->find('list', ['fields'=>$f,'contain'=>$c,'order'=>'Quantity.name','limit'=>100,'recursive'=>-1]);
        $this->set('conds',$conds);
	}

   	/**
     * view a condition
	 * not a particularly useful function to see one condition out of context
	 * however it does show how to search for data in related tables...
     * @param int $cid
     * @return void
     */
    public function view(int $cid)
    {
		$cs=['Condition.id'=>$cid];$c=['Dataset','Dataseries','Datapoint','Quantity','System','Compohnent'=>['Chemical'=>['Substance']],'Phase'=>['Phasetype'],'Unit'];
        $cond=$this->Condition->find('first',['conditions'=>$cs,'contain'=>$c,'limit'=>100,'recursive'=>-1]);
        $this->set('cond',$cond);
    }

	// functions requiring login (not in Auth::allow)

    /**
     * delete a condition
     * @param int $id
	 * @return void
	 */
    public function delete(int $id)
    {
        $this->Condition->delete($id);
        $this->redirect('/');
    }

}
