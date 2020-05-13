<?php

/**
 * Class ReportsController
 * ReportsController
 */
class ReportsController extends AppController {

    public $uses=array('Journal','System','Identifier','SubstancesSystem','File','Report','Dataset','Dataseries',
        'Datapoint','Condition','Data','Setting','Annotation');

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * View a list of the Reports
     */
    public function index()
    {
        $data=$this->Report->find('list',['fields'=>['id','title'],'order'=>['id'], "limit"=>100]);
        $this->set('data',$data);

        $propCount=[];
        foreach ($data as $id => $prop) {
            {
                $propCount[$id]= $this->Dataset->find('list',array('fields'=>['id']));
            }

        }
        $this->set('propCount',$propCount);
    }

    /**
     * Report add function
     */
    public function add()
    {
        if($this->request->is('post')) {
            $this->Report->create();
            if($this->Report->save($this->request->data)) {
                $this->Flash->set('The report has been added');
                $this->redirect(array('action'=>'index'));
            } else {
                $this->Flash->set('The report count not be added');
            }
        }
    }

    /**
     * View a Report
     * @parem
     * @type
     */
    public function view($id,$type=null)
    {
        $chmf=['formula','orgnum','source','substance_id'];
        $ref =['id','journal','authors','year','volume','issue','startpage','endpage','title','url'];
        $con =['id','datapoint_id','system_id','property_name','number','significand','exponent','unit_id','accuracy'];
        $prop =['id','name','phase','field','label','symbol','definition','updated'];
        $file =['filename','url','datapoints'];
        $c=['Dataset'=>[
                'Annotation',
                'Dataseries'=>[
                    'Condition'=>['fields'=>$con,'Unit', 'Property', 'Annotation'],
                    'Setting'=>['Unit', 'Property'],
                    'Datapoint'=>[
                        'Annotation',
                        'Condition'=>['fields'=>$con,'Unit', 'Property'=>['fields'=>$prop]],
                        'Data'=>['Unit', 'Property'=>['fields'=>$prop],'Sampleprop'],
                        'Setting'=>['Unit', 'Property']
                    ],
                    'Annotation'
                ],
                'File'=>['fields'=>$file,'Chemical'=>['fields'=>$chmf]],
                'Reactionprop',
                'Sampleprop'
            ],
            'Reference'=>['fields'=>$ref, 'Journal'],
            'System'=>[
                'Substance'=>[
                    'Identifier'=>['fields'=>['type','value']]
                ]
            ]
        ];

        $data=$this->Report->find('first',['conditions'=>['Report.id'=>$id],'contain'=>$c,'recursive'=>-1]);


        //debug($data);exit;//
        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($data)."]";
            exit;
        }
        $this->set('data',$data);
    }

    }

