<?php

/**
 * Class ReportsController
 * Actions related to dealing with data reports
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class ReportsController extends AppController {

    public $uses=['Report','Dataset'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('index','view');
    }

    /**
     * view a list of reports
	 * @return void
	 */
    public function index()
    {
        $data=$this->Report->find('list',['fields'=>['id','title'],'order'=>['id'],'recursive'=>-1]);
        $this->set('data',$data);
   }

	/**
	 * view a report
	 * @param int $id
	 * @return void
	 */
	public function view(int $id)
	{
		$chmf=['orgnum','sourcetype','substance_id'];
		$ref=['id','authors','year','volume','issue','startpage','endpage','title','doi'];
		$con=['id','datapoint_id','system_id','number','significand','exponent','unit_id','accuracy'];
		$prop=['id','name','phase','field','label','symbol','definition','updated'];
		$file=['filename','points'];
		$c=['Dataset'=>[
				'System'=>[
					'Substance'=>[
						'Identifier'=>['fields'=>['type','value']]
					]
				],
				'Dataseries'=>[
					'Condition'=>['fields'=>$con,'Unit','Quantity'],
					'Datapoint'=>[
						'Condition'=>['fields'=>$con,'Unit','Quantity'=>['fields'=>$prop]],
						'Data'=>['Unit','Quantity'=>['fields'=>$prop],'Sampleprop']
					]
				]
			],
			'File'=>['fields'=>$file,'Chemical'=>['fields'=>$chmf]],
			'Reference'=>['fields'=>$ref,'Journal']
		];
		$data=$this->Report->find('first',['conditions'=>['Report.id'=>$id],'contain'=>$c,'recursive'=>-1]);
		if($this->request->is('ajax')) {
			header('Content-Type: application/json');
			echo "[".json_encode($data)."]";exit;
		}
		$this->set('data',$data);
	}

	// functions requiring login (not in Auth::allow)

	/**
     * add a report
	 * @return void
	 */
    public function add()
    {
        if($this->request->is('post')) {
            if($this->Report->add($this->request->data)) {
                $this->Flash->set('The report has been added');
                $this->redirect(['action'=>'index']);
            } else {
                $this->Flash->set('The report count not be added');
            }
        }
    }

}
