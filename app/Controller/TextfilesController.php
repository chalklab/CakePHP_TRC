<?php
require_once(APP."Vendor".DS."Reader.php");

/**
 * Class TextFilesController
 */
class TextFilesController extends AppController
{
    public $uses=['File','TextFile','Publication','Ruleset','Rule','Report','Propertytype','Property','Activity','Dataset','Char','Saxon'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('totalfiles');
    }

    /**
     * List the text files
     */
    public function index()
    {
        $data=$this->TextFile->find('list',['fields'=>['id','version','file_id'],'order'=>['file_id']]);
        $this->set('data',$data);
        $files=$this->File->find('list',['fields'=>['id','filename','publication_id'],'order'=>['publication_id']]);
        $this->set('files',$files);
        $pubs=$this->Publication->find('list',['fields'=>['id','title']]);
        $this->set('pubs',$pubs);
        $dataset=$this->Dataset->find('list',['fields'=>['file_id','id'],'order'=>['file_id']]);
        $this->set('dataset',$dataset);

    }

    /**
     * Add a new text file
     * @param mixed $id
     * @return mixed
     */
    public function add($id=null)
    {
        if (!empty($this->data)||$id!=null) {

            // Get id if submitted by form
            if(!empty($this->data)&&!isset($this->data['ajax'])) {
                $id=$this->data['TextFile']['inputFile'];
            }

            // Get the file of interest (and the associated data from other tables)
            // TODO: Ruleset change (two places)
            $c=['Publication',
                'Propertytype'=>[
                    'Ruleset'=>[
                        'Rule'=>[
                            'Ruletemplate'=>['fields'=>['name','blocks']],
                            'RulesRulesnippet'=>['order'=>['block'],
                                'Property',
                                'Unit',
                                'Rulesnippet'=>[
                                    'fields'=>['name','mode','regex'],
                                    'Metadata',
                                    'Property',
                                    'Unit'
                                ]
                            ]
                        ]
                    ],
                    'Variable'],
                'Ruleset'=>[
                    'Rule'=>[
                        'Ruletemplate'=>['fields'=>['name','blocks']],
                        'RulesRulesnippet'=>['order'=>['block'],
                            'Property',
                            'Unit',
                            'Rulesnippet'=>[
                                'fields'=>['name','mode','regex'],
                                'Metadata',
                                'Property',
                                'Unit'
                            ]
                        ]
                    ]
                ]
            ];
            $file=$this->File->find('first',['conditions'=>['File.id'=>$id],'contain'=>$c,'recursive'=>-1]);
            $filetitle=$file['File']['title'];
            if($file['File']['filetype']=="pdf"||$file['File']['filetype']=="txt") {
                // Get path to the pdftotext for the server
                $pdfToTextPath=$this->File->pdf2txtpath();

                // Construct the path to the file name
                if($file['File']['filetype']=='txt') {
                    $fileToExtract=WWW_ROOT.'files'.DS.'text'.DS.$file['File']['publication_id'].DS.$file['File']['filename'];
                } else {
                    $fileToExtract=WWW_ROOT.'files'.DS.'pdf'.DS.$file['File']['publication_id'].DS.$file['File']['filename'];
                }

                // Get the text out of the file
                $res=$file['File']['resolution'];
                if(isset($file['File']['ruleset_id']) && $file['File']['ruleset_id'] != 0 && $file['File']['ruleset_id'] != null) {
                    // This is phase 2
                    $ruleset = $this->Ruleset->find("first", ['conditions' => ['Ruleset.id' => $file['File']['ruleset_id']], 'recursive' => 2]);
                    if((int)$file['File']['pdf_version'] == 0) {
                        // Get the text file contents
                        $text = file_get_contents($fileToExtract);
                    } else {
                        // Run the PDF extraction
                        $text = shell_exec($pdfToTextPath.' -enc UTF-8 -layout -r '.$res.' "'.$fileToExtract.'" -');
                    }
                } else {
                    // This is phase 1 code (legacy)
                    if ((int)$file['File']['format'] == 1 || (int)$file['File']['format'] == 2) {
                        if ((int)$file['File']['pdf_version'] == 0) {
                            // Get the text file contents
                            $text = file_get_contents($fileToExtract);
                        } else {
                            // Run the PDF extraction
                            $text = shell_exec($pdfToTextPath.' -enc UTF-8 -layout -r 300 "'.$fileToExtract.'" -');
                        }
                        if ($file['File']['publication_id'] == 4 || $file['File']['publication_id'] == 5) {
                            $ruleset = $this->Ruleset->find("first", ['conditions' => ['Ruleset.id' => 4], 'recursive' => 2]);
                        } else {
                            $ruleset = $this->Ruleset->find("first", ['conditions' => ['Ruleset.id' => ((int)$file['File']['format'] + 1)], 'recursive' => 2]);
                        }
                    } elseif ((int)$file['File']['format'] == 7) {
                        if ((int)$file['File']['pdf_version'] == 0) {
                            // Get the text file contents
                            $text = file_get_contents($fileToExtract);
                        } else {
                            // Run the PDF extraction
                            $text = shell_exec($pdfToTextPath . ' -enc UTF-8 -layout -r 300 "' . $fileToExtract . '" -');
                        }
                        $ruleset = $this->Ruleset->find("first", ['conditions' => ['Ruleset.id' => 15], 'recursive' => 2]);
                    } else {
                        if ((int)$file['File']['pdf_version'] == 0) {
                            // Get the text file contents
                            $text = file_get_contents($fileToExtract);
                        } else {
                            // Run the PDF extraction
                            $text = shell_exec($pdfToTextPath . ' -enc UTF-8 -layout -r 300 -H ' . $file['Propertytype']['height'] . ' -W ' . $file['Propertytype']['width'] . ' "' . $fileToExtract . '" -');
                        }
                        if ((int)$file['File']['format'] == 3 && $file['Propertytype']['Ruleset'] != 5) {
                            $ruleset = $this->Ruleset->find("first", ['conditions' => ['Ruleset.id' => 5], 'recursive' => 2]);
                        } else {
                            $ruleset = $this->Ruleset->find("first", ['conditions' => ['Ruleset.id' => $file['Propertytype']['Ruleset']['id']], 'recursive' => 2]);
                        }
                    }
                }

                // Remove unwanted characters
                $type=mb_detect_encoding($text);
                if($type=="UTF-8") {
                    $text=$this->Char->clean($text);
                }

                // Get Rules
                if(!empty($file['Ruleset'])) {
                    $rules=$file['Ruleset']['Rule'];
                } elseif(!empty($file['Propertytype']['Ruleset'])) {
                    $rules=$file['Propertytype']['Ruleset']['Rule'];
                } else {
                    die('No ruleset found (on file or propertytype');
                }

                // All data from rules to feed into filling debug, trash, errors
                $setdata=$this->Rule->setdata($rules);
                $regexes=$setdata['regexes'];$actions=$setdata['actions'];$layouts=$setdata['layouts'];$rows=$setdata['rows'];
                $blocks=$setdata['blocks'];$types=$setdata['types'];$fields=$setdata['fields'];$cmpdnums=$setdata['cmpdnums'];
                $datatypes=$setdata['datatypes'];$rmodes=$setdata['rmodes'];$smodes=$setdata['smodes'];$units=$setdata['units'];
                $properties=$setdata['properties'];$metadata=$setdata['metadata'];$scidata=$setdata['scidata'];

                // Split out the rows that match the rules from those that don't
                if(stristr($text,"\r")) {
                    $tarray=explode("\r",$text);
                } else {
                    $tarray=explode("\n",$text);
                }

                $keep=$disp=$ftrash=$empty=[];
                foreach($tarray as $linenum=>$line) {
                    if (trim($line)=="") {
                        $empty[$linenum]=$line;
                    } else {
                        foreach($regexes as $step=>$regex) {
                            if(preg_match('/'.$regex.'/mu',$line)) {
                                $keep[$linenum]=$line;$disp[$linenum]="(".$step.") ".$line;
                                break;
                            }
                        }
                        if(!isset($keep[$linenum])) {
                            $ftrash[$linenum]=$line;
                        }
                    }
                }

                // Many systems or just 1?
                if($file['File']['num_systems']>1) {
                    // Save the arrays for potential review later
                    $path=WWW_ROOT."files".DS."text".DS.$file['File']['publication_id'];
                    $folder = new Folder($path,true,0777);
                    $kfp=fopen(substr($path.DS.$file['File']['filename'],0,-4).'_keep.txt','w');
                    fwrite($kfp,implode("\n",$keep));
                    fclose($kfp);
                    $tfp=fopen(substr($path.DS.$file['File']['filename'],0,-4).'_trash.txt','w');
                    fwrite($tfp,implode("\n",$ftrash));
                    fclose($tfp);
                    $efp=fopen(substr($path.DS.$file['File']['filename'],0,-4).'_empty.txt','w');
                    fwrite($efp,implode("\n",$empty));
                    fclose($efp);
                    // Delete lines before the first line that matches the regex
                    $startptn='/'.$regexes[1].'/mu';
                    foreach($keep as $linenum=>$line) {
                        if(preg_match($startptn,$line)) {
                            break;
                        } else {
                            unset($keep[$linenum]);
                        }
                    }
                    // Delete lines after a STOP action if it exists
                    $stop=0;
                    foreach($rules as $rule) {
                        if($rule['RulesRuleset']['action']=="STOP") {
                            $stop=$rule['RulesRuleset']['step']; break;
                        }
                    }
                    if($stop>0) {
                        $peek=array_reverse($keep);
                        foreach($peek as $linenum=>$line) {
                            if(preg_match('/'.$regexes[$stop].'/mu',$line)) {
                                unset($peek[$linenum]);
                                break;
                            } else {
                                unset($peek[$linenum]);
                            }
                        }
                        $keep=array_reverse($peek);
                    }
                    // Chunk text
                    $tfiles=[];$j=0;$prev="no";
                    foreach($keep as $line) {
                        if(preg_match($startptn,$line)) {
                            if($prev=="no") {
                                $j++;$prev="yes";
                            }
                        } else {
                            $prev="no";
                        }
                        $tfiles[$j][]=$line;
                    }

                    // Capture the data as text files
                    foreach($tfiles as $tid=>$tfile) {

                        // Process the textfile using the rules
                        // $p format ['db','trash','debug','error']
                        $p=$this->TextFile->process($tfile,$setdata,$tid); // Passing $tid only to be able to debug in process
                        //debug($p);exit;
                        // Save to the textfiles table
                        $title="System ".$tid." in '".$file['File']['title']."'";
                        $prev=$this->TextFile->find('list',['fields'=>['id','updated'],'conditions'=>['title'=>$title]]);
                        $ver=count($prev)+1;
                        $captured=json_encode($p['db'],JSON_PRESERVE_ZERO_FRACTION);
                        $debug=json_encode($p['debug'],JSON_PRESERVE_ZERO_FRACTION);
                        $trash=json_encode($p['trash'],JSON_PRESERVE_ZERO_FRACTION);
                        $errors=json_encode($p['errors'],JSON_PRESERVE_ZERO_FRACTION);
                        $data=['title'=>$title,'file_id'=>$id,'text'=>implode("\n",$tfile),'sysnum'=>$tid,'debug'=>$debug,
                            'captured'=>$captured, 'trash'=>$trash,'errors'=>$errors,'version'=>$ver];

                        if(!$this->TextFile->insert($data)) {
                            debug($this->TextFile->validationErrors);debug($data);exit;
                        }
                    }
                    // Update the file status
                    $this->File->id=$id;
                    $this->File->saveField("status","extracted");
                    $this->File->saveField("captured",json_encode($keep));
                    $this->File->saveField("trash",json_encode($ftrash));
                    // Record this activity
                    $activity=['user_id'=>$this->Session->read('Auth.User.id'),
                        'file_id'=>$id,'step_num'=>2,'type'=>'extract','comment'=>count($tfiles)." text files extracted"];
                    $this->Activity->create();
                    $this->Activity->save(['Activity'=>$activity]);
                    // Redirect to the file to view all textfiles
                    return $this->redirect('/files/view/'.$id);
                } else {
                    // One system
                    $tfile=array_values($keep);

                    // Process the textfile using the rules
                    // $p format ['db','trash','debug','error']
                    $p=$this->TextFile->process($tfile,$setdata);

                    // Save to the textfiles table
                    $title="System in '".$filetitle."'";
                    $prev=$this->TextFile->find('list',['fields'=>['id','updated'],'conditions'=>['title'=>$title]]);
                    $ver=count($prev)+1;
                    $captured=json_encode($p['db'],JSON_PRESERVE_ZERO_FRACTION);
                    $debug=json_encode($p['debug'],JSON_PRESERVE_ZERO_FRACTION);
                    $trash=json_encode($p['trash'],JSON_PRESERVE_ZERO_FRACTION);
                    $errors=json_encode($p['errors'],JSON_PRESERVE_ZERO_FRACTION);
                    $data=['title'=>$title,'file_id'=>$id,'text'=>implode("\n",$tfile),'sysnum'=>1,'debug'=>$debug,
                        'captured'=>$captured, 'trash'=>$trash,'errors'=>$errors,'version'=>$ver];

                    // Add the text file
                    $new=$this->TextFile->insert($data);
                    if($new) {
                        // Update the file status
                        $this->File->id=$id;
                        $this->File->saveField("status","extracted");
                        // Record this activity
                        $activity=['user_id'=>$this->Session->read('Auth.User.id'),
                            'file_id'=>$id,'step_num'=>2,'type'=>'extract','comment'=>"1 text file extracted"];
                        $this->Activity->create();
                        $this->Activity->save(['Activity'=>$activity]);
                        // Redirect
                        if ($this->request->is('ajax')) {
                            die('{ "status": "success", "id": "'.$new['id'].'", "title": "'.$new['title'].'", "errors": '.$new['errors'].' }');
                        } else {
                            return $this->redirect('/textfiles/view/'.$new['id']);
                        }
                    } else {
                        $errors=$this->TextFile->validationErrors;
                        $ferrors=Hash::flatten($errors);
                        die('{ "status": "failure", "errors": '.json_encode($ferrors).' }');
                    }
                }

            } elseif ($file['File']['filetype']=="xml") {
                $f=$file['File'];$p=$file['Publication'];
                $xpath=WWW_ROOT.Configure::read('xmlfilepath').$p['id'].DS.$f['filename'];
                $tpath=WWW_ROOT.Configure::read('xsltfilepath').$f['xslt'];
                $jpath=WWW_ROOT.Configure::read('jsonfilepath').$p['id'].DS.str_replace("xml",'json',$f['filename']);;
                $savedjson=$this->Saxon->transform($f['filename'],$f['xslt'],'json',$p['id']);
                if($savedjson) {
                    $prev=$this->TextFile->find('list',['fields'=>['id','updated'],'conditions'=>['file_id'=>$f['id']]]);
                    $ver=count($prev)+1;
                    $xml=file_get_contents($xpath);
                    $json=file_get_contents($jpath);
                    $title="System in '".$filetitle."'";
                    $data=['title'=>$title,'file_id'=>$id,'text'=>$xml,'sysnum'=>1,'debug'=>'[]',
                        'captured'=>$json,'trash'=>'[]','errors'=>'[]','version'=>$ver];

                    // Add the text file
                    $new=$this->TextFile->insert($data);

                    if($new) {
                        // Update the file status
                        $this->File->id=$id;
                        $this->File->saveField("status","extracted");
                        // Record this activity
                        $activity=['user_id'=>$this->Session->read('Auth.User.id'),
                            'file_id'=>$id,'step_num'=>2,'type'=>'extract','comment'=>"1 xml file extracted"];
                        $this->Activity->create();
                        $this->Activity->save(['Activity'=>$activity]);
                        // Redirect
                        if ($this->request->is('ajax')) {
                            die('{ "status": "success", "id": "'.$new['id'].'", "title": "'.$new['title'].'", "errors": '.$new['errors'].' }');
                        } else {
                            return $this->redirect('/textfiles/view/'.$new['id']);
                        }
                    } else {
                        die('{ "status": "failure", "errors": "Text file not added" }');
                    }
                } else {
                    die('{ "status": "failure", "errors": "JSON not created" }');
                }
            } else {
                die('{ "status": "failure", "errors": "Need to write code for this filetype!" }');
            }
        } else {
            $rules=$this->Ruleset->find('list',['fields'=>['id','name']]);
            $file = $this->File->find('list', ['fields'=>['id','filename']]);
            $this->set('file', $file);
            $this->set('rulesets', $rules);
        }
    }

    /**
     * View a text file
     * @param $id
     */
    public function view($id)
    {
        $tfile=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id]]);
        $file=$this->File->find('first',['conditions'=>['File.id'=>$tfile['TextFile']['file_id']],'recursive'=>1]);
        if(isset($_GET['submitGithubIssue'])){
            $args=array();
            $args['title']="Problem with File ".$file['File']['filename'];
            $args['body']=$this->Session->read('Auth.User.username')." reported ".$_POST['body'];
            $args['assignees'][] = "stuchalk";

            $token=Configure::read("github.token");
            $req = curl_init();
            curl_setopt($req,CURLOPT_URL,"https://api.github.com/repos/stuchalk/Springer/issues?access_token=".$token);
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_USERAGENT, "Whinis Springer app");
            curl_setopt($req, CURLOPT_POSTFIELDS, json_encode($args));
            curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req, CURLOPT_TIMEOUT, 30);
            $result = curl_exec($req);
            $http_code = curl_getinfo($req,  CURLINFO_HTTP_CODE);
            $http_errno = curl_errno($req);
            echo $result;
            curl_close($req);
            die();
        }
        $this->set('tfile',$tfile['TextFile']);
        $path=Configure::read('path');
        $pdf=$path.'/files/pdf/'.$file['File']['publication_id'].'/'.$file['File']['filename'];
        $this->layout='wide';
        $this->set('pdf',$pdf);
        $this->set('path',$path);
    }

    /**
     * Update a text file
     * @param integer $id
     */
    public function update($id)
    {
        if(!empty($this->request->data))
        {
            $this->TextFile->id=$id;
            $this->TextFile->save($this->request->data);
            $this->redirect('/textfiles/view/'.$id);
        } else {
            $textfile=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id]]);
            $this->set('textfile',$textfile);
            $this->set('id',$id);
            $rules=$this->Ruleset->find('list',['fields'=>['id','name']]);
            $this->set('rulesets', $rules);
            $file=$this->File->find('list',['fields'=>['id','filename']]);
            $this->set('file', $file);
        }
    }

    /**
     * Edit a textfile
     * @param $id
     * @throws Exception
     */
    public function edit($id){
        if(!empty($this->request->data)) {
            $textfile=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id]]);
            $textfile['TextFile']['text']=$this->request->data['text'];

            //added contain for file
            $this->File->Behaviors->load('Containable');
            $file=$this->File->find('first',['conditions'=>['File.id'=>$textfile['TextFile']['file_id']], 'contain' => ['Propertytype.Ruleset.Rule','Propertytype.Variable']]); //get the file of interest

            $fileToExtract=WWW_ROOT.'files'.DS.'pdf'.DS.$file['File']['publication_id'].DS.$file['File']['filename'];// find the path to the file name

            if((int)$file['File']['format']==1||(int)$file['File']['format']==2){
                $ruleset=$this->Ruleset->find("first",['conditions'=>['Ruleset.id'=>((int)$file['File']['format']+1)],'recursive'=>2]);
            }elseif((int)$file['File']['format']==7) {
                $ruleset=$this->Ruleset->find("first",['conditions'=>['Ruleset.id'=>15],'recursive'=>2]);
            }else{
                if((int)$file['File']['format']==3&&$file['Propertytype']['Ruleset']!=5){
                    $ruleset=$this->Ruleset->find("first",['conditions'=>['Ruleset.id'=>5],'recursive'=>2]);
                }else {
                    $ruleset = $file['Propertytype']['Ruleset'];
                }

            }
            //$rulesetID=$ruleset['Ruleset']['id'];
            //var_dump($rulesetID);
            //die();
            $ruleset=$this->Ruleset->generateRulesetArray($ruleset); //convert ruleset to a compatible config
            if(empty($ruleset)) {
                die('{"error":"File improperly converted, No ruleset found"}');
            }
            $textfile['TextFile']['text'] = preg_replace('~\R~', "\r\n",$textfile['TextFile']['text']);
            for($i=128;$i<=255;$i++){

                $textfile['TextFile']['text'] = str_replace(chr($i), '', $textfile['TextFile']['text']);

            }
            $Reader=new Reader(); //initialize reader
            $textfile['TextFile']['text']=$Reader->FixCharacters($textfile['TextFile']['text'],Configure::read("textReplacementArray")); //Replace mis read characters
            $stream = fopen('php://temp','r+'); //save the text to a stream so that the reader can use it
            fwrite($stream, $textfile['TextFile']['text']);
            rewind($stream); //set the stream back to position 0;

            $Reader->SetConfig($ruleset); //set the config for this file
            $Reader->setStream($stream); //load the file stream into the reader
            $Json=$Reader->ReadFile(); //extract the data
            if(empty($Json)){
                die('{"error":"Reading Text Returned Empty Array, Double Check Ruleset"}');
            }

            //var_dump($Json['rawData']);
            if((int)$file['File']['format']>4) {
                //asort($Json['rawData']);
                $Json['split']=array();
                $tempData=array();
                $tempUnits=array();
                $original=""; //stores the first unit so that we can recalculate split
                $processing=true;
                $index=0;
                $Json['split'][]=0;
                while ($processing) {
                    $matched=false;
                    foreach ($Json['rawData'] as $key => $point) {
                        if(isset($point[$index])){
                            $tempData[]=$point[$index];
                            $tempUnits[]=$key;
                            $matched=true;
                        }
                    }
                    if(!$matched){
                        $processing=false;
                    }
                    $Json['split'][]=count($tempData);
                    $index++;
                }
                //var_dump($tempData);
                if(isset($Json['Parameters'])&&$file['Propertytype']['id']>67) {
                    $tempParams = array();
                    $tempParamUnit = array();
                    foreach ($Json['Parameters'] as $i => $param) {
                        $tempParams[$Json['split'][$i]] = $param;
                        $tempParamUnit[$Json['split'][$i]] = $Json['ParametersUnit'][$i];
                    }
                    $Json['Parameters']=$tempParams;
                    $Json['ParametersUnit']=$tempParamUnit;
                }
                $counter=0;
                $inc=0;
                $Json['Data']=$tempData;
                $Json['DataUnits']=$tempUnits;
                unset($Json['rawData']);
            }
            if(!is_array($Json['CAS'])) {
                $Json['chemicalName']=str_replace($Json['chemicalFormula'],"",$Json['chemicalName']);
                $Json['chemicalName'] = trim(str_replace($Json['CAS'], "", $Json['chemicalName']));
            } else {
                foreach ($Json['CAS'] as $i => $cas) { //create an easily ingestible array for the chemical finder
                    $Json['chemicalName'][$i]=str_replace($Json['chemicalFormula'][$i],"",$Json['chemicalName'][$i]);
                    $Json['chemicalName'][$i] = trim(str_replace($Json['CAS'][$i], "", $Json['chemicalName'][$i]));
                }
            }
            // Remove lingering square brackets in names
            if(is_array($Json['chemicalName'])) {
                foreach($Json['chemicalName'] as $i=>$name) {
                    $Json['chemicalName'][$i]=trim(str_replace("[]","",$name));
                }
            }
            $i=0; //use this while loop to make sure the data is in the right columns

            $propertiesCount=0;
            if($file['File']['format']=='0'){
                foreach ($file['Propertytype']['Variable'] as $var) {
                    if (strpos($var['identifier'], "Error") === false){
                        $propertiesCount++;
                    }
                }
                while(isset($Json['Data'][0][$i])) {
                    if ($i != 0) { //if this is not the first item
                        if (!isset($Json['Data'][0][$i-1])|| strlen($Json['Data'][0][$i-1]) != strlen($Json['Data'][0][$i])) { //check if this one has more spaces in front than the last one
                            for($q=0;$q<$propertiesCount;$q++) {
                                $Json['Data'][$q+$propertiesCount][] = $Json['Data'][$q][$i]; //copy the data

                                unset($Json['Data'][$q][$i]); //remove it from first column
                            }


                        }
                    }
                    $i++;
                }
            }
            if(isset($Json['commentLine'])){
                $comment="";
                if(is_array($Json['commentLine'])) { //combine the comment line into a single line
                    foreach ($Json['commentLine'] as $c) {
                        $comment .= $c;
                    }
                }else{
                    $comment=$Json['commentLine'];
                }
                $Json['commentLine']=array();
                //cascade that looks for a-e comments and places them
                if(($bPos=strpos($comment,'b)'))!==false){
                    $Json['commentLine']['a)']=substr($comment,3,$bPos-3);
                    if(($cPos=strpos($comment,'c)'))!==false){
                        $Json['commentLine']['b)']=substr($comment,$bPos+2,$cPos-2);
                        if(($dPos=strpos($comment,'d)'))!==false){
                            $Json['commentLine']['c)']=substr($comment,$cPos+2,$dPos-2);
                            if(($ePos=strpos($comment,'e)'))!==false){
                                $Json['commentLine']['d)']=substr($comment,$dPos+2,$ePos-2);
                                $Json['commentLine']['d)']=substr($comment,$ePos+2);
                            }else{
                                $Json['commentLine']['d)']=substr($comment,$dPos+2);
                            }
                        }else{
                            $Json['commentLine']['c)']=substr($comment,$cPos+2);
                        }
                    }else{
                        $Json['commentLine']['b)']=substr($comment,$bPos+2);
                    }
                }else{
                    $Json['commentLine']['a)']=substr($comment,2);
                }
                $remove=array();
                if(isset($Json['comment'])&& is_array($Json['comment'])) {
                    foreach ($Json['comment'] as $i => &$comment) {
                        $comment = trim($comment);
                        if ($comment != '') {
                            $comment = trim($Json['commentLine'][$comment]);
                        } else {
                            $remove[] = $i;
                        }
                    }
                }else if(isset($Json['comment'])){
                    unset($Json['comment']);
                }
                foreach($remove as $i){
                    unset($Json['comment'][$i]);
                }
                unset($Json['commentLine']);

            }

            foreach($Json as $key=>&$result)
            {
                if(strpos($key,"Parameter")===false&&strpos($key,"Data")===false&&strpos($key,"reference")===false&&strpos($key,"comment")===false) {
                    if (is_array($result) && count($result) == 1) {
                        $result = $result[0];
                    }
                }
            }
            array_walk_recursive($Json,"trim_array"); //clean up all the extras spaces left behind
            $textfile['TextFile']['extracted_data']=json_encode($Json);
            $textfile['TextFile']['version']=$textfile['TextFile']['version']+1;
            unset($textfile['TextFile']['id']);
            $this->TextFile->clear();
            $this->TextFile->create();
            if($this->TextFile->save($textfile)) {
                die('{"result":"success","id":'.$this->TextFile->id.'}');
            }else{
                die('{"result":"error"}');
            }
        }
    }

    /**
     * Reprocess a textfile
     * @param integer $id
     * @return mixed
     */
    public function newversion($id=0)
    {
        // Get text from POST variable
        if($id==0) {
            $id=3;
            $prev=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>3],'fields'=>['text','title','file_id','sysnum','version'],'recursive'=>1]);
            $text=$prev['TextFile']['text'];
        } else {
            if(isset($this->request->data['text'])) { // Is there text? not if testing
                $text=$this->request->data['text'];
                $prev=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id],'fields'=>['title','file_id','sysnum','version'],'recursive'=>1]);
            } else {
                $prev=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id],'fields'=>['text','title','file_id','sysnum','version'],'recursive'=>1]);
                $text=$prev['TextFile']['text'];
            }
        }
        $tfile=explode("\n",$text);

        // Get rules
        $c=['File'=>[
                'Ruleset'=>[
                    'Rule'=>[
                        'Ruletemplate'=>['fields'=>['name','blocks']],
                        'RulesRulesnippet'=>['order'=>['block'],
                            'Property',
                            'Unit',
                            'Rulesnippet'=>[
                                'fields'=>['name','mode','regex','scidata'],
                                'Metadata',
                                'Property',
                                'Unit'
                            ]
                        ]
                    ]
                ]
            ]];
        $file=$this->TextFile->find('first',['conditions'=>['TextFile.id'=>$id],'contain'=>$c,' recursive=>-1']);

        if(isset($file['File']['Ruleset']['Rule'])) {
            $rules=$file['File']['Ruleset']['Rule'];
        } elseif(isset($file['File']['xslt'])) {
            $xslt=$file['File']['xslt'];
            echo "You need to code this part...";exit;
        }

        //debug($rules);exit;

        // All data from rules to feed into filling debug, trash, errors
        $setdata=$this->Rule->setdata($rules);

        // Process the textfile using the rules
        // $p format ['db','trash','debug','error']
        $p=$this->TextFile->process($tfile,$setdata);

        //exit;
        // Save to the textfiles table
        $title=$prev['TextFile']['title'];
        $sysnum=$prev['TextFile']['sysnum'];
        $fileid=$prev['TextFile']['file_id'];
        $ver=$prev['TextFile']['version']+1;
        $captured=json_encode($p['db'],JSON_PRESERVE_ZERO_FRACTION);
        $debug=json_encode($p['debug'],JSON_PRESERVE_ZERO_FRACTION);
        $trash=json_encode($p['trash'],JSON_PRESERVE_ZERO_FRACTION);
        $errors=json_encode($p['errors'],JSON_PRESERVE_ZERO_FRACTION);
        $data=['title'=>$title,'file_id'=>$fileid,'text'=>$text, 'captured'=>$captured,'debug'=>$debug,
            'trash'=>$trash,'errors'=>$errors,'version'=>$ver,'sysnum'=>$sysnum];

        $new=$this->TextFile->insert($data);
        if($new) {
            // Update the previous version status
            $this->TextFile->id=$id;
            $this->TextFile->saveField('status','retired');
            if($this->request->is('ajax')) {
                echo $new['id'];exit;
            } else {
                return $this->redirect('/textfiles/view/'.$new['id']);
            }
        } else {
            debug($this->TextFile->validationErrors);debug($data);exit;
        }

    }

    /**
     * Delete a text file
     */
    public function delete($id)
    {
        $this->TextFile->delete($id);
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Count of files
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->TextFile->find('count');
        return $data;
    }

    /**
     * Mass process files
     * @param null $id
     * @param null $type
     * @return mixed
     */
    public function massprocess($id=null,$type=null)
    {
        if($this->request->is('post')) {
            return $this->redirect(['action' => 'massprocess',$this->data['File']['publication_id']]);
        } elseif(is_null($id)) {
            $pubs = $this->Publication->find('list', ['fields' => ['id', 'title']]);
            $this->set('pubs', $pubs);
        } else {
            $pub = $this->Publication->find('list', ['fields' => ['id', 'title'],'conditions'=>['id'=>$id]]);
            $this->set('pub', $pub);
            $this->set('pubid',$id);
            $path=Configure::read('path');
            $c = ['TextFile' => ['fields' => ['id', 'title', 'status', 'version', 'errors'], 'order' => 'sysnum', 'conditions' => ['NOT' => ['status' => 'retired']],
                'Dataset' => ['fields' => ['id'],
                    'Report' => ['fields' => ['id', 'title']],
                    'Dataseries' => ['fields' => ['id'],
                        'Datapoint' => ['fields' => ['id']],
                        'Equation' => ['fields' => ['id']]
                    ],
                    'Reference' => ['fields' => ['id']]
                ]
            ]
            ];
            if(is_null($type)) {
                $files=$this->File->find('all',['fields'=>['id','title','filename','propertytype_id','status'],'conditions'=>['publication_id'=>$id],'order'=>'File.id','contain'=>$c,'recursive'=>-1]);
            } else {
                $files=$this->File->find('all',['fields'=>['id','title','filename','propertytype_id','status'],'conditions'=>['publication_id'=>$id,'title like'=>'%'.$type.'%'],'order'=>'File.id','contain'=>$c,'recursive'=>-1]);
            }
            foreach($files as $i=>$file) {
                $tfstats=$this->fieldstats($file['TextFile'],'status');
                $files[$i]['tfstats']=$tfstats;
            }
            $this->set('files',$files);
            $this->set('path',$path);
        }
    }

    /**
     * Statistics about a field in the text_files table
     * @param $array
     * @param $field
     * @return array
     */
    private function fieldstats($array,$field)
    {
        $stats=[];
        foreach($array as $item) {
            if(array_key_exists($item[$field],$stats)) {
                $stats[$item[$field]]=$stats[$item[$field]]+1;
            } else {
                $stats[$item[$field]]=1;
            }
        }
        return $stats;
    }

    /** Clean a textfile (delete all data under it via reports
     * @param $id
     */
    public function clean($id)
    {
        $this->TextFile->clean($id);
        $this->TextFile->id=$id;
        $this->TextFile->saveField('status',"added");
        exit;
    }
}

//used to trim the array
function trim_array(&$item, $key)
{
    $item=trim($item);
}