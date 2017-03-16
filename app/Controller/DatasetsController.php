<?php

/**
 * Class DatasetsController
 */
class DatasetsController extends AppController
{
    public $uses=['Dataset','Publication','Report','Quantity','Dataseries','Parameter','Variable','Substance','File','Reference'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('totalfiles','scidata');
    }

    /**
     * View a data set
     * @param integer $id
     * @return mixed
     */
    public function view($id)
    {
        $c=['Annotation',
            'Dataseries'=>[
                'Condition'=>['Unit', 'Property'],
                'Setting'=>['Unit', 'Property'],
                'Datapoint'=>[
                    'Annotation',
                    'Condition'=>['Unit', 'Property'],
                    'Data'=>['Unit', 'Property'],
                    'Setting'=>['Unit', 'Property'],
                    'SupplementalData'=>['Metadata', 'Unit', 'Property']
                ],
                'Equation'=> ['fields'=>['title'],
                    'Eqntype'=>['fields'=>['name','latex']],
                    'Eqnvar'=>['fields'=>['code','max','min'],'order'=>['index'],
                        'Unit'=>['fields'=>['name','symbol']],
                        'Property'=>['fields'=>['name','symbol','definition']]],
                    'Eqnterm'=>['fields'=>['code','type','value','error'],'order'=>['index'],
                        'Unit'=>['fields'=>['name','symbol']],
                        'Property'=>['fields'=>['name','symbol','definition']]],
                    'Annotation'=>['fields'=>['type','text']],
                    'Setting'=>['fields'=>['number','error','accuracy','exponent','text'],
                        'Unit'=>['fields'=>['name','symbol']],
                        'Property'=>['fields'=>['name','symbol','definition']]
                    ],
                    'SupplementalData'=>['fields'=>['number','error','accuracy','exponent','text'],
                        'Unit'=>['fields'=>['name','symbol']],
                        'Property'=>['fields'=>['name','symbol','definition']]
                    ]
                ],
                'Annotation'
            ],
            'Propertytype'=> [
                'Variable'=>['Property'],
                'Parameter'=>['Property']
            ],
            'System'=>['Substance'=>['Identifier'=>['fields'=>['type','value']]]],
            'Report'=>['fields'=>['title','file_code']],
            'File'=>['Publication'],
            'Reference',
            'TextFile'=>['fields'=>['id','title']]
        ];
        $dump=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        $this->set('dump',$dump);
        $tfid=$dump['Dataset']['text_file_id'];
        // Get a list of datsets that come from the same textfile
        $related=$this->Dataset->find('list',['conditions'=>['Dataset.text_file_id'=>$tfid,'NOT'=>['Dataset.id'=>$id]],'recursive'=>1]);
        $this->set('related',$related);
        $this->set('dsid',$id);
        if($this->request->is('ajax')) {
            $title=$dump['Dataset']['title'];
            echo '{ "title" : "'.$title.'" }';exit;
        }
    }

    /**
     * View index of data sets
     */
    public function index()
    {
        $c=['File'=>['fields'=>['id','title'],'order'=>['title'],'Dataset'=>['fields'=>['id','title'],'order'=>'title']]];
        $data=$this->Publication->find('all',['fields'=>['id','title'],'order'=>['title'],'contain'=>$c,'recursive'=>1]);
        $this->set('data',$data);
    }

    /**
     * Function to find the most recent datasets
     * @return mixed
     */
    public function recent()
    {
        $data=$this->Dataset->find('list',['order'=>['updated'=>'desc'],'limit'=>6]);
        if($this->request->params['requested']) { return $data; }
        $this->set('data',$data);
    }

    /**
     * Total files
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->Dataset->find('count');
        return $data;
    }

    /**
     * Generate SciData
     * @param $id
     * @param $down
     */
    public function scidata($id,$down="")
    {
        // Note: there is an issue with the retrival of substances under system if id is not requested as a field
        // This is a bug in CakePHP as it works without id if its at the top level...
        $contains=[
            'Dataseries'=>[
                'Condition'=>['Unit',
                    'Property'=>['fields'=>['name'],
                        'Quantity'=>['fields'=>['name']]]],
                'Setting'=>['Unit', 'Property'=>['fields'=>['name'],
                    'Quantity'=>['fields'=>['name']]]],
                'Datapoint'=>[
                    'Condition'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Data'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'Setting'=>['Unit',
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]],
                    'SupplementalData'=>['Unit',
                        'Metadata'=>['fields'=>['name']],
                        'Property'=>['fields'=>['name'],
                            'Quantity'=>['fields'=>['name']]]]],
                'Annotation'
            ],
            'Propertytype'=> [
                'Variable'=>['Property'=>['fields'=>['name'],
                                'Quantity'=>['fields'=>['name']]]],
                'Parameter'=>['Property'=>['fields'=>['name'],
                                'Quantity'=>['fields'=>['name']]]]
            ],
            'System'=>['fields'=>['id','name','description','type'],
                'Substance'=>['fields'=>['name','formula','molweight'],
                    'Identifier'=>['fields'=>['type','value'],'conditions'=>['type'=>['inchi','inchikey','iupacname']]]]
            ],
            'Report',
            'File'=>['Publication'],
            'Reference'

        ];
        $data=$this->Dataset->find('first',['conditions'=>['Dataset.id'=>$id],'contain'=>$contains,'recursive'=>-1]);
        //debug($data);exit;

        $rpt=$data['Report'];
        $ptype=$data['Propertytype'];
        $set=$data['Dataset'];
        $file=$data['File'];
        $pub=$file['Publication'];
        $ref=$data['Reference'];
        $ser=$data['Dataseries'];
        $sys=$data['System'];
        //debug($ser);exit;

        // Other systems -> related
        $othersys=$this->Dataset->find('list',['fields'=>['id'],'conditions'=>['system_id'=>$sys['id'],'propertytype_id'=>$ptype['id'],'NOT'=>['Dataset.id'=>$id]]]);
        //debug($othersys);exit;

        // Base
        $base="https://chalk.coas.unf.edu/springer/datasets/scidata/".$id."/";

        // Build the PHP array that will then be converted to JSON
        $json['@context']=['https://stuchalk.github.io/scidata/contexts/scidata.jsonld',
            ['sci'=>'http://stuchalk.github.io/scidata/ontology/scidata.owl#',
                'meas'=>'http://stuchalk.github.io/scidata/ontology/scidata_measurement.owl#',
                'qudt'=>'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
                'dc'=>'http://purl.org/dc/terms/',
                'xsd'=>'http://www.w3.org/2001/XMLSchema#'],
            ['@base'=>$base]];

        // Main metadata
        $json['@id']="";
        $json['uid']="springer:dataset:".$id;
        $json['title']=$rpt['title'];
        $json['author']=[];
        if($ref['authors']!=null) {
            if(stristr($ref['authors'],'[{')) {
                $authors=json_decode($ref['authors'],true);
            } else {
                $authors=explode(", ",$ref['authors']);
            }
            $acount=1;
            foreach ($authors as $au) {
                $json['author'][]=['@id'=>'author/'.$acount,'@type'=>'dc:creator','name'=>$au];
                $acount++;
            }
        }
        $json['description']=$rpt['title'];
        $json['publisher']='Springer Nature';
        $json['startdate']=$set['updated'];
        $json['permalink']="http://chalk.coas.unf.edu/springer/datasets/view/".$id;
        foreach($othersys as $os) {
            $json['related'][]="http://chalk.coas.unf.edu/springer/datasets/view/".$os;
        }
        $json['toc']=['@id'=>'toc','@type'=>'dc:tableOfContents','sections'=>[]];

        // Process data series to split out conditions, settings, and parameters
        $datas=$conds=$setts=$supps=[];
        foreach($ser[0]['Datapoint'] as $p=>$point) {
            foreach($point['Data'] as $d=>$dval) {
                $datas[$d][$p]=$dval;
            }
            foreach($point['Condition'] as $c=>$cval) {
                $conds[$c][$p]=$cval;
            }
            foreach($point['Setting'] as $s=>$sval) {
                $setts[$s][$p]=$sval;
            }
            foreach($point['SupplementalData'] as $u=>$uval) {
                $supps[$u][$p]=$uval;
            }
        }
        //debug($datas);debug($conds);debug($setts);debug($supps);exit;

        // SciData
        $setj['@id']="scidata";
        $setj['@type']="sci:scientificData";
        $json['scidata']=$setj;

        // Settings
        $metj=[];
        if(!empty($setts)) {
            // Methodology
            $metj['@id']='methodology';
            $metj['@type']='sci:methodology';
            $metj['evaluation']='experimental';
            $metj['aspects']=[];
            $json['toc']['sections'][] = $metj['@id'];
            $meaj['@id'] = 'measurement/1';
            $meaj['@type'] = 'meas:measurement';
            $json['toc']['sections'][] = $meaj['@id'];
            $meaj['settings'] = [];
            foreach($setts as $sid=>$sett) {
                //debug($sett);exit;
                $setgj = [];
                $setgj['@id'] = "setting/".($sid + 1);
                $setgj['@type'] = "sci:setting";
                $setgj['quantity'] = strtolower($sett[0]['Property']['Quantity']['name']);
                $setgj['property'] = $sett[0]['Property']['name'];
                foreach ($sett as $sidx => $s) {
                    $v=$vs=[];
                    if(!in_array($s['number'],$vs)) {
                        $vs[]=$s['number'];
                        $v['@id'] = "setting/" . ($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                        $v['@type'] = "sci:value";
                        if (!is_null($s['number'])) {
                            $v['number'] = $s['number'];
                            if (isset($s['Unit']['symbol']) && !empty($s['Unit']['symbol'])) {
                                $v['unitref'] = $this->Dataset->qudt($s['Unit']['symbol']);
                            }
                        } else {
                            $v['text'] = $s['text'];
                        }
                        $setgj['value'] = $v;

                    }
                    $setts[$sid][$sidx]['slink'][]="setting/".($sid + 1) . "/value/".(array_search($s['number'],$vs)+1);
                }
                $meaj['settings'][] = $setgj;
            }
            $metj['aspects'][] = $meaj;
        }
        $json['scidata']['methodology']=$metj;

        // System
        $sysj=[];
        if(is_array($sys)&&!empty($sys)||is_array($conds)&&!empty($conds)) {
            $json['toc']['sections'][]="system";
            $sysj['@id']='system';
            $sysj['@type']='sci:system';
            $sysj['discipline']='chemistry';
            $sysj['subdiscipline']='physical chemistry';
            $sysj['facets']=[];
        }

        // System sections
        // Mixture/Substance
        $type='';
        if(is_array($sys)&&!empty($sys)) {
            // System
            if (count($sys['Substance']) == 1) {
                $type = "substance";
            } else {
                $type = "mixture";
            }
            $sid = $type . "/1";
            $json['toc']['sections'][] = $sid;
            $mixj['@id'] = $sid;
            $mixj['@type'] = "sci:" . $type;
            $opts = ['name', 'description', 'type'];
            foreach ($opts as $opt) {
                if (isset($sys[$opt]) && $sys[$opt] != "") {
                    $mixj[$opt] = $sys[$opt];
                }
            }
            if (isset($sys['Substance'])) {
                for ($j = 0; $j < count($sys['Substance']); $j++) {
                    // Components
                    $subj['@id'] = $sid . "/component/" . ($j + 1);
                    $subj['@type'] = "sci:chemical";
                    $subj['source'] = "compound/" . ($j + 1);
                    $mixj['components'][] = $subj;
                    // Chemicals
                    $sub = $sys['Substance'][$j];
                    $chmj['@id'] = "compound/" . ($j + 1);
                    $json['toc']['sections'][] = $chmj['@id'];
                    $chmj['@type'] = "sci:compound";
                    $opts = ['name', 'formula', 'molweight'];
                    foreach ($opts as $opt) {
                        if (isset($sub[$opt]) && $sub[$opt] != "") {
                            $chmj[$opt] = $sub[$opt];
                        }
                    }
                    if (isset($sub['Identifier'])) {
                        $opts = ['inchi', 'inchikey', 'iupacname'];
                        foreach ($sub['Identifier'] as $idn) {
                            foreach ($opts as $opt) {
                                if ($idn['type'] == $opt) {
                                    $chmj[$opt] = $idn['value'];
                                }
                            }
                        }
                    }
                    $sysj['facets'][] = $chmj;
                }
            }
            $sysj['facets'][] = $mixj;
        }
        // Conditions
        if(is_array($conds)&&!empty($conds)) {
            foreach($conds as $cid=>$cond) {
                //debug($cond);exit;
                $v=$vs=$condj = [];
                $condj['@id'] = "condition/".($cid + 1);
                $json['toc']['sections'][] = $condj['@id'];
                $condj['@type'] = "sci:condition";
                $condj['quantity'] = strtolower($cond[0]['Property']['Quantity']['name']);
                $condj['property'] = $cond[0]['Property']['name'];
                foreach ($cond as $cidx => $c) {
                    if(!in_array($c['number'],$vs)) {
                        $vs[]=$c['number'];
                        $v['@id'] = "condition/" . ($cid + 1) . "/value/".(array_search($c['number'],$vs)+1);
                        $v['@type'] = "sci:value";
                        if (!is_null($c['number'])) {
                            $v['number'] = $c['number'];
                            if (isset($c['Unit']['symbol']) && !empty($c['Unit']['symbol'])) {
                                $v['unitref'] = $this->Dataset->qudt($c['Unit']['symbol']);
                            }
                        } else {
                            $v['text'] = $c['text'];
                        }
                        $condj['value'][] = $v;
                    }
                    $conds[$cid][$cidx]['clink'][]="condition/".($cid+1)."/value/".(array_search($c['number'],$vs)+1);
                }
                $sysj['facets'][] = $condj;
            }
        }
        $json['scidata']['system']=$sysj;

        // Data
        $resj=[];
        if(is_array($datas)&&!empty($datas)) {
            $json['toc']['sections'][] = "dataset";
            $resj['@id'] = 'dataset';
            $resj['@type'] = 'sci:dataset';
            $resj['source'] = 'measurement/1';
            $resj['scope'] = $type . '/1';
            $resj['datagroup'] = [];
            // Group
            foreach($datas as $did=>$data) {
                $grpj['@id']='datagroup/'.($did+1);
                $json['toc']['sections'][] = $grpj['@id'];
                $grpj['@type'] = 'sci:datagroup';
                $grpj['quantity']=strtolower($data[0]['Property']['Quantity']['name']);
                $grpj['property']=$data[0]['Property']['name'];
                foreach($data as $d=>$dtm) {
                    $dtmj=[];
                    $dtmj['@id'] = 'datagroup/'.($did+1).'/datapoint/'.($d+1);
                    $dtmj['@type'] = 'sci:datapoint';
                    $dtmj['conditions']=$conds[$did][$d]['clink'];
                    if(!empty($setts)) {
                        $dtmj['settings']=$setts[$did][$d]['slink'];
                    } else {
                        $dtmj['settings']=[];
                    }
                    // Value
                    $v=[];
                    if(!is_null($dtm['number'])) {
                        $unit="";
                        if(isset($dtm['Unit']['symbol'])&&!empty($dtm['Unit']['symbol'])) {
                            $unit=$this->Dataset->qudt($dtm['Unit']['symbol']);
                        }
                        if($dtm['datatype']=="datum") {
                            $v['@id']=$dtmj['@id']."/value";
                            $v['@type']="sci:value";
                            $v['number']=$dtm['number'];
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['value']=$v;
                        } else {
                            $v['@id']=$dtmj['@id']."/valuearray";
                            $v['@type']="sci:valuearray";
                            $v['numberarray']=json_decode($dtm['number'],true);
                            if($unit!="") { $v['unitref']=$unit; }
                            $dtmj['valuearray']=$v;
                        }
                    }
                    $grpj['datapoint'][]=$dtmj;
                }
                $resj['datagroup'][]=$grpj;
            }
        }
        $json['scidata']['dataset']=$resj;

        // Sources
        // Original Paper
        $paper=['@id'=>'reference/1','@type'=>'dc:source'];
        if($ref['bibliography']!=null) {
            $paper['citation'] = $ref['bibliography'];
        } elseif($ref['citation']!=null) {
            $paper['citation'] = $ref['citation'];
        }
        if(isset($ref['doi'])&&$ref['doi']!=null) {
            $paper['url']="http://dx.doi.org/".$ref['doi'];
        }
        if(isset($ref['url'])&&$ref['url']!=null) {
            $paper['url']=$ref['url'];
        }
        // Springer Publication
        $volume=['@id'=>'reference/2','@type'=>'dc:source'];
        $volume['citation'] = $pub['citation'];
        if(isset($pub['doi'])&&$pub['doi']!=null) {
            $volume['url']="http://dx.doi.org/".$pub['doi'];
        }
        if(isset($pub['url'])&&$pub['url']!=null) {
            $volume['url']=$pub['url'];
        }
        if(isset($pub['eisbn'])&&$pub['eisbn']!=null) {
            $volume['eisbn'] = $pub['eisbn'];
        }
        $json['references'][]=$paper;
        $json['references'][]=$volume;

        // Rights
        $json['rights']=['@id'=>'rights','@type'=>'dc:rights'];
        $json['rights']['holder']='Springer Nature/Nature Publishing Group, San Francisco, CA 94104';
        $json['rights']['license']='http://creativecommons.org/publicdomain/zero/1.0/';
        //debug($json);exit;

        // OK turn it back into JSON-LD
        header("Content-Type: application/ld+json");
        if($down=="download") { header('Content-Disposition: attachment; filename="'.$id.'.jsonld"'); }
        echo json_encode($json,JSON_UNESCAPED_UNICODE);exit;

    }
}