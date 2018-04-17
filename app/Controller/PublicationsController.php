<?php

/**
 * Class PublicationsController
 * Publications Controller
 */
class PublicationsController extends AppController {

    public $uses=array('Publication','System','Identifier','SubstancesSystem','File','Report','Dataset','Dataseries','Datapoint','Condition','Data','SupplementalData','Setting','Annotation');

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow();
    }

    /**
     * View a list of the publications
     */
    public function index()
    {
        $data=$this->Publication->find('list', ['fields'=>['id','title','phase'],'order'=>['title']]);
        $this->set('data',$data);

        $propCount=[];
        foreach($data as $group=>$phase) {
            foreach ($phase as $id => $prop) {
                $propCount[$id] = $this->File->find('count',['conditions'=>['publication_id'=>$id]]);
            }
        }
        $this->set('propCount',$propCount);

    }

    /**
     * Publication add function
     */
    public function add()
    {
        if($this->request->is('post')) {
            $this->Publication->create();
            if($this->Publication->save($this->request->data)) {
                $this->Flash->set('The publication has been added');
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Flash->set('The publication could not be added.');
            }
        }
    }

    /**
     * View a publication
     * @param $id
     * @param $type
     */
    public function view($id,$type=null)
    {
        if(is_null($type)) {
            $c=['File'=>['fields'=>['id','title','status'],
                'TextFile'=>['fields'=>['id','title','status','errors'],'conditions'=>["NOT"=>["status"=>"retired"]],
                    'Dataset'=>['fields'=>['id','title']]
                ],
                'Ruleset'=>['fields'=>['id','name']]
            ],
                'Property',
                'Report'=>[
                    'Dataset'=>[
                        'System'
                    ]
                ]
            ];
        } else {
            $c=['File'=>['fields'=>['id','title','status'],'conditions'=>['title like'=>'%'.$type.'%'],
                'TextFile'=>['fields'=>['id','title','status','errors'],'conditions'=>["NOT"=>["status"=>"retired"]],
                    'Dataset'=>['fields'=>['id','title']]
                ],
                'Ruleset'=>['fields'=>['id','name']]
            ],
                'Property',
                'Report'=>[
                    'Dataset'=>[
                        'System'
                    ]
                ]
            ];
        }
        $data=$this->Publication->find('first',['conditions'=>['Publication.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($data)."]";
            exit;
        }
        $this->set('data',$data);
    }

    /**
     * Publication search function
     * @param int $id
     */
    public function search($id)
    {
        $c=[
            'Property',
            'Ruleset',
            'Report'=>[
                'Dataset'=>[
                    'System'
                ]
            ]
        ];
        $data=$this->Publication->find('first',['conditions'=>['Publication.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        $systems=[];
        if(!empty($data['Report'])) {
            foreach($data['Report'] as $report){
                if(isset($report['Dataset']['System'])) {
                    $first=substr($report['Dataset']['System']['name'],0,1);
                    if(!isset($systems[$first])){
                        $systems[$first]=array();
                    }
                    if(!isset($systems[$first][$report['Dataset']['System']['name']])){
                        $systems[$first][$report['Dataset']['System']['name']]=array();
                    }
                    $systems[$first][$report['Dataset']['System']['name']][]=$report;
                } else {
                    // TODO: Remove?  There is not system on the report...
                }
            }
            ksort($systems,SORT_NATURAL);
            $data['Systems']=$systems;
        } else {
            $data['Systems']=[];
        }

        if($this->request->is('ajax')) {
            header('Content-Type: application/json');
            echo "[".json_encode($data)."]";
            exit;
        }
        $this->set('data',$data);
    }

    /**
     * Publication update function
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->is('post')) {
            $this->Publication->id=$id;
            if($this->Publication->save($this->request->data)) {
                $this->Flash->set('The publication has been updated');
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Flash->set('The publication could not be updated.');
            }

        } else {
            $data=$this->Publication->find('first',['conditions'=>['id'=>$id]]);
            $this->set('data',$data['Publication']);
            $this->set('args',array('id'=>$id));
        }
    }

    /**
     * Publication delete function
     * @param int $id
     */
    public function delete($id)
    {
        $this->Publication->delete($id);
        $this->redirect("/publications/");
    }

    /**
     * Delete all data that is associated with this publication (but not the publication)
     * @param $id
     * @return mixed
     */
    public function clean($id)
    {
        // Get all the files and delete them (cascades to all the data below)
        $files=$this->File->find('list',['fields'=>['id','filename'],'conditions'=>['publication_id'=>$id]]);
        foreach($files as $fid=>$title) {
            $this->File->delete($fid);
        }
        $reports=$this->Report->find('list',['fields'=>['id','title'],'conditions'=>['publication_id'=>$id]]);
        foreach($reports as $rid=>$title) {
            $this->Report->delete($rid);
        }
        return $this->redirect('/publications/view/'.$id);
    }

    /**
     * Delete all data that is associated with this publication (but not the publication)
     * @param $id
     * @return mixed
     */
    public function rclean($id)
    {
        // Get all the reports and delete them (cascades to all the data below)
        $reports=$this->Report->find('list',['fields'=>['id','title'],'conditions'=>['publication_id'=>$id]]);
        foreach($reports as $rid=>$title) {
            $this->Report->delete($rid);
        }
        return $this->redirect('/publications/view/'.$id);
    }

    /**
     * Get count of files in publication
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->Publication->find('count');
        return $data;
    }

    /**
     * Publication map function
     */
    public function map()
    {
        $data = $this->Publication->find('list',['fields'=>['id','title','phase'],'order' => ['title']]);
        ksort($data);
        $this->set('data', $data);
    }

    /**
     * SQL Export
     * @param $id
     */
    public function sql($id) {
        $path=WWW_ROOT.'sql'.DS.'publication_'.$id.'.sql';
        $rpts=$sets=$sers=$pnts=$cnds=$dats=$sups=$anns=$rptanns=$setanns=$seranns=$pntanns=$stgs=$syss=$subs=$idns=$serconds=[];
        $rpts=$this->Report->find('list',['conditions'=>['Report.publication_id'=>$id],'fields'=>['Report.id'],'recursive'=>0]);
        $sets=$this->Dataset->find('list',['conditions'=>['Dataset.report_id'=>$rpts],'fields'=>['Dataset.id'],'recursive'=>0]);
        $sers=$this->Dataseries->find('list',['conditions'=>['Dataseries.dataset_id'=>$sets],'fields'=>['Dataseries.id'],'recursive'=>0]);
        $pnts=$this->Datapoint->find('list',['conditions'=>['Datapoint.dataseries_id'=>$sers],'fields'=>['Datapoint.id'],'recursive'=>0]);
        $cnds=$this->Condition->find('list',['conditions'=>['Condition.datapoint_id'=>$pnts],'fields'=>['Condition.id'],'recursive'=>0]);
        $sercnds=$this->Condition->find('list',['conditions'=>['Condition.dataseries_id'=>$sers],'fields'=>['Condition.id'],'recursive'=>0]);
        $dats=$this->Data->find('list',['conditions'=>['Data.datapoint_id'=>$pnts],'fields'=>['Data.id'],'recursive'=>0]);
        $sups=$this->SupplementalData->find('list',['conditions'=>['SupplementalData.datapoint_id'=>$pnts],'fields'=>['SupplementalData.id'],'recursive'=>0]);
        $stgs=$this->Setting->find('list',['conditions'=>['Setting.datapoint_id'=>$pnts],'fields'=>['Setting.id'],'recursive'=>0]);
        $rptanns=$this->Annotation->find('list',['conditions'=>['Annotation.report_id'=>$rpts],'fields'=>['Annotation.id'],'recursive'=>0]);
        $setanns=$this->Annotation->find('list',['conditions'=>['Annotation.dataset_id'=>$sets],'fields'=>['Annotation.id'],'recursive'=>0]);
        $seranns=$this->Annotation->find('list',['conditions'=>['Annotation.dataseries_id'=>$sers],'fields'=>['Annotation.id'],'recursive'=>0]);
        $pntanns=$this->Annotation->find('list',['conditions'=>['Annotation.datapoint_id'=>$dats],'fields'=>['Annotation.id'],'recursive'=>0]);
        $anns=$rptanns+$setanns+$seranns+$pntanns;
        $syss=$this->Dataset->find('list',['conditions'=>['Dataset.report_id'=>$rpts],'fields'=>['Dataset.system_id'],'group'=>['Dataset.system_id'],'recursive'=>0]);
        $refs=$this->Dataset->find('list',['conditions'=>['Dataset.report_id'=>$rpts],'fields'=>['Dataset.reference_id'],'group'=>['Dataset.reference_id'],'recursive'=>0]);
        $subs=$this->SubstancesSystem->find('list',['conditions'=>['SubstancesSystem.system_id'=>$syss],'fields'=>['SubstancesSystem.substance_id'],'group'=>['SubstancesSystem.substance_id'],'recursive'=>0]);
        $idns=$this->Identifier->find('list',['conditions'=>['Identifier.substance_id'=>$rpts],'fields'=>['Identifier.id'],'recursive'=>0]);


        echo "Reports: ".count($rpts),"<br />";
        echo "Datasets: ".count($sets),"<br />";
        echo "Dataseries: ".count($sers),"<br />";
        echo "Series Conditions: ".count($sercnds),"<br />";
        echo "Datapoints: ".count($pnts),"<br />";
        echo "Conditions: ".count($cnds),"<br />";
        echo "ExptData: ".count($dats),"<br />";
        echo "Supplemental Data: ".count($sups),"<br />";
        echo "Settings: ".count($stgs),"<br />";
        echo "Annotations (on reports): ".count($rptanns),"<br />";
        echo "Annotations (on datasets): ".count($setanns),"<br />";
        echo "Annotations (on dataseriess): ".count($seranns),"<br />";
        echo "Annotations (on datapoints): ".count($pntanns),"<br />";
        echo "Systems: ".count($syss),"<br />";
        echo "Substances: ".count($subs),"<br />";
        echo "Identifiers: ".count($idns),"<br />";
        echo "References: ".count($refs),"<br />";
        //exit;

        $output=[];
        if($this->Utils->sql('Publication',$id,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Report',$rpts,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Dataset',$sets,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Dataseries',$sers,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Datapoint',$pnts,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Condition',$cnds,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Data',$dats,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('SupplementalData',$sups,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Setting',$stgs,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Annotation',$anns,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('System',$syss,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Substance',$subs,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Identifier',$idns,$output)) {
            $output[]=''; // Extra line
        }
        if($this->Utils->sql('Reference',$refs,$output)) {
            $output[]=''; // Extra line
        }
        $text=implode("\r",$output);
        //echo $text;exit;
        //debug($output);exit;
        $sqlpath=WWW_ROOT.'sql'.DS.'publication_'.$id.'.sql';
        $fp=fopen($sqlpath,'w');
        fwrite($fp,$text);
        fclose($fp);
        exit;
    }
}