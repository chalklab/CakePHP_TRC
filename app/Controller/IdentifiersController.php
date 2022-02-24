<?php

/**
 * Class IdentifiersController
 * actions related to working with (compound) identifiers table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class IdentifiersController extends AppController
{

    public $uses=['Identifier','Substance'];

    /**
     * function beforeFilter
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

	// functions requiring login (not in Auth::allow)

	/**
	 * search the CommonChemistry website by InChIkey using the API and a redirect
	 * either redirects to CommonChemistry or to the homepage of this site with Flash error message
	 * @param string $key
	 */
	public function ccbykey(string $key)
	{
		$path = "https://commonchemistry.cas.org/api/search?q=InChIKey=";
		if(preg_match('/[A-Z]{14}-[A-Z]{10}-[ANB]/',$key)) {
			$HttpSocket = new HttpSocket();
			$apiresp=$HttpSocket->get($path.$key);
			$json = json_decode($apiresp->body(),true);
			// check if any hits
			if($json['count']>0) {
				// get the first hit in the list
				$hit=$json['results'][0];
				$this->redirect('https://commonchemistry.cas.org/detail?cas_rn='.$hit['rn']);
			} else {
				$this->Flash->set('Not found on the CommonChemistry website...');
				$this->redirect('/');
			}
		} else {
			$this->Flash->set('Invalid InChIKey!');
			$this->redirect('/');
		}
	}

	/**
	 * remove abandoned identifiers (no substance)
	 * @return void
	 */
	public function clean()
	{
		$subs=$this->Identifier->find('list',['fields'=>['substance_id'],'group'=>['substance_id']]);
		foreach($subs as $sub) {
			$res=$this->Substance->find('first',['conditions'=>['id'=>$sub],'recursive'=>-1]);
			if(empty($res)) {
				$this->Identifier->deleteAll(['substance_id'=>$sub],false);
				echo "Deleted: ".$sub."<br />";
			} else {
				echo "Retained: ".$sub."<br />";
			}
		}
		echo count($subs)."<br />";
		exit;
	}

}
