<?php
set_time_limit(0);

/**
 * Class FilesController
 */
class FilesController extends AppController {

    public $uses = ['File','Char','Publication','Propertytype','Property','Rule',
        'Activity','TextFile','Reference','Errors','Report','Ruleset','Saxon','Dataset'];

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('totalfiles','map');
    }

    /**
     * Add a file
     * @param integer $phase (project phase)
     * @return mixed
     */
    public function add($phase=2)
    {
        if($this->request->is('post')) {
            $uploadedFile=$this->request->data['File'];
            if($uploadedFile['uploaded']) {
                // Already uploaded so go
                if($uploadedFile['filetype']=="xml") {
                    // Get list of XML files for publication
                    debug($uploadedFile);
                    $pubid=$uploadedFile['publication_id'];
                    $prpid=$uploadedFile['property_id'];
                    $path=WWW_ROOT.Configure::read('xmlfilepath').DS.$pubid;
                    $dir= new Folder($path);
                    $files=$dir->find('.*\.xml');
                    // Loop through files
                    foreach($files as $file) {
                        // Add a file to files table
                        $curr=$this->File->find('first',['conditions'=>['filename'=>$file]]);
                        $fpath=$path.DS.$file;
                        if(empty($curr)) {
                            $f=[];
                            $f['filename']=$file;
                            $f['publication_id']=$pubid;
                            $f['property_id']=$prpid;
                            $f['filename']=$file;
                            $f['filesize']=filesize($fpath);
                            $f['filetype']='xml';
                            $f['num_systems']=1;
                            $f['comment']='Processed using XSLT after FTP upload';
                            $this->File->create();
                            $this->File->save(['File'=>$f]);
                            $fid=$this->File->id;
                            $this->File->clear();
                        } else {
                            $fid=$curr['File']['id'];
                        }
                        // Add the file to text_files table
                        $curr=$this->TextFile->find('first',['conditions'=>['file_id'=>$fid]]);
                        if(empty($curr)) {
                            $t=[];
                            $t['text']=file_get_contents($fpath);
                            $t['file_id']=$fid;
                            $t['version']=1;
                            $this->TextFile->create();
                            $this->TextFile->save(['TextFile'=>$t]);
                            $tid=$this->TextFile->id;
                            $this->TextFile->clear();
                        } else {
                            $tid=$curr['TextFile']['id'];
                        }
                        // Do the XSLT
                        $xslt=$this->Saxon->transform($file,$pubid.".xsl","jsonld",$pubid);

                        debug($tid);exit;
                        // Save output file to text_files table
                        // Ingest data
                    }
                }
                exit;
            } else {
                $size=filesize($uploadedFile['file']['tmp_name']);
                //$mime=mime_content_type($uploadedFile['file']['tmp_name']);
                $mime=$uploadedFile['file']['type'];
                $files=[];$i=0;
                if($mime=="application/zip") {
                    $zip = new ZipArchive; //open zip file
                    $res = $zip->open($this->request->data['File']['file']['tmp_name']);
                    if($res) {
                        // Extract all files
                        $zippath=WWW_ROOT.'temp'.DS.$uploadedFile['file']['name'];
                        $zip->extractTo($zippath);
                        $zip->close();
                        $dir=WWW_ROOT.'temp'.DS.$uploadedFile['file']['name'];
                        $name = explode(".", $uploadedFile['file']['name']);
                        if (is_dir($dir . DS . $name[0])) {
                            $dir = $dir . DS . $name[0];
                        }
                        if(isset($uploadedFile['xslt'])) {
                            // Get the XSLT files currently uploaded to the VM
                            $xsldir = new Folder(WWW_ROOT.'files/xslt');
                            $xslts = $xsldir->find('.*\.xsl');
                        }
                        $filesScan = scandir($dir);
                        foreach($filesScan as $i => $file) {
                            if(preg_match('/^\./',$file,$matches)) {
                                //debug($matches);
                                continue;
                            }
                            if(stristr($file,"sm_tpp")) {
                                $name=str_replace("sm_tpp_","",substr($file,0,-4));
                                $name=str_replace("_","-",$name);
                                $tmp['title']=str_replace("*NAME*",strtoupper($name),$uploadedFile['title']);
                            } else {
                                list($name,$chapter)=explode("_",substr($file,0,-4),2);
                                if(stristr($chapter,"_")) {
                                    list($temp,$chapter)=explode("_",$chapter);
                                }
                                $tmp['title']=str_replace("*C*",$chapter,$uploadedFile['title']);
                            }
                            $tmp['path'] = $dir.DS.$file;
                            $tmp['filesize'] = filesize($tmp['path']);
                            $tmp['filename'] = $file;
                            $tmp['type'] = mime_content_type($tmp['path']);
                            if(isset($uploadedFile['xslt'])) {
                                $tmp['xslt'] = $xslts[$uploadedFile['xslt']];
                            }
                            if(strstr($tmp['type'],'pdf')) {
                                $tmp['resolution']='600'; // Default
                            } else {
                                $tmp['resolution']=null;
                            }
                            $tmp['zip'] = true;
                            $files[] = $tmp;
                        }
                    } else {
                        die('File could not be unzipped...');
                    }
                } elseif ($mime=="text/xml"||$mime=="application/xml") {
                    $tmp['filesize'] = $size;
                    $tmp['type'] = $mime;
                    $tmp['filename'] = $uploadedFile['file']['name'];
                    $tmp['path'] = $uploadedFile['file']['tmp_name'];
                    $tmp['resolution']=null;
                    // Get the XSLT files currently uploaded to the VM
                    $dir = new Folder(WWW_ROOT.'files/xslt');
                    $xslts = $dir->find('.*\.xsl');
                    $tmp['xslt'] = $xslts[$uploadedFile['xslt']];
                    $tmp['zip'] = false;
                    $files[$i] = $tmp;
                } elseif ($mime=="text/plain") {
                    $tmp['filesize'] = $size;
                    $tmp['type'] = $mime;
                    $tmp['filename'] = $uploadedFile['file']['name'];
                    $tmp['path'] = $uploadedFile['file']['tmp_name'];
                    $tmp['resolution']=null;
                    $tmp['zip'] = false;
                    $files[$i] = $tmp;
                } elseif ($mime=="application/pdf") {
                    $tmp['filesize'] = $size;
                    $tmp['type'] = $mime;
                    $tmp['filename'] = $uploadedFile['file']['name'];
                    $tmp['path'] = $uploadedFile['file']['tmp_name'];
                    $tmp['resolution']='600'; // Default
                    $tmp['zip'] = false;
                    $files[$i]=$tmp;
                } else {
                    $this->Flash->set('Filetype cannot be processed...');
                }


                // Save the files and add to the DB
                foreach($files as $idx=>$file){
                    // Add filesize and type
                    if(!isset($file['filesize'])||empty($file['filesize'])) {
                        $file['filesize'] = filesize($file['path']);
                    }
                    if(!isset($file['type'])||empty($file['type'])) {
                        $file['type'] = mime_content_type($file['path']);
                    }
                    // Check to see if this is file that has already been uploaded if so delete all versions
                    //$fileDelete=$this->File->find('all',['conditions' => ['File.filename'=> $file['filename']]]);
                    //if (!empty($fileDelete)) {
                    //    foreach ($fileDelete as $set) {
                    //        $this->File->delete($set['File']['id'],true);
                    //    }
                    //}

                    // Move uploaded file to storage location
                    if ($file['type']==="application/pdf") {
                        $path=WWW_ROOT."files".DS."pdf".DS.$uploadedFile['publication_id'];
                    } elseif ($file['type']==="application/xml"||$file['type']==="text/xml"||stristr($file['filename'],"xml")) {
                        $path=WWW_ROOT."files".DS."xml".DS.$uploadedFile['publication_id'];
                    } else {
                        $path=WWW_ROOT."files".DS."text".DS.$uploadedFile['publication_id'];
                    }

                    $folder = new Folder($path,true,0777);
                    if ($file['zip']) {
                        // rename moves the file from one location to another (!)
                        $destination=$path.DS.$file['filename'];
                        rename($file['path'],$destination);
                    } elseif(isset($file['text'])&&!empty($file['text'])) {
                        // For text files...
                        file_put_contents($path.DS.$file['filename'],$file['text']);
                    } else {
                        move_uploaded_file($file['path'],$path.DS.$file['filename']);
                        $file['path']=$path.DS.$file['filename'];
                    }
                    // Get PDF version using Utility component function
                    $file['pdf_version']=null;
                    if($file['type']==="application/pdf") {
                        $file['pdf_version'] = $this->Utils->pdfVersion($path . DS . $file['filename']);
                    }
                    // Add URL to file on Springer website
                    $pub=$this->Publication->find('first',['conditions'=>['Publication.id'=>$uploadedFile['publication_id']]]);
                    if($pub['Publication']['publisher']=="Springer") {
                        if ($file['type']==="application/xml"||$file['type']==="text/xml") {
                            $chunks=explode("_",$file['filename']);
                            $file['url']="http://link.springer.com/chapter/10.1007/".$chunks[0];
                        } else {
                            $file['url']="http://link.springer.com/chapter/10.1007/".str_replace(".pdf","",$file['filename']);
                        }
                    } else {
                        $file['url']=null;
                    }
                    // Get publication propcode
                    if(!isset($uploadedFile['propCode'])||$uploadedFile['propCode']===0) {
                        $code = $this->File->getCode($file['filename'], $uploadedFile['publication_id']);
                        if (!isset($code) || $code === false) {
                            if (!empty($this->request->params['requested'])) {
                                return "File Missing Property Code";
                            } else {
                                print 'File Missing Property Code';
                                $this->Flash->set('File Missing Property Code');
                            }
                        }
                        $propertytype = $this->Propertytype->find('first', ['conditions' => ['Propertytype.code' => $code]]);
                        if (!empty($propertytype)) {
                            $proptypeid = $propertytype['Propertytype']['id'];
                        } else {
                            $proptypeid = 0;
                            if (!empty($this->request->params['requested'])) {
                                return "Invalid Property Code : " . $code;
                            } else {
                                print 'Invalid Property Code :';
                                $this->Flash->set('Invalid Property Code : ' . $code);
                            }
                        }
                    } elseif ($uploadedFile['propCode']=='') {
                        // Get this from the propertytypes table
                        $ptype=$this->Propertytype->find('first',['conditions'=>['Propertytype.property_id'=>$uploadedFile['property_id']]]);
                        if(!empty($ptype['Propertytype'])) {
                            $file['propertytype_id']=$ptype['Propertytype']['id'];
                        } else {
                            $file['propertytype_id']=null;
                        }
                    } else {
                        // Needed?
                        $file['propertytype_id'] = $uploadedFile['propCode'];
                        $file['format'] = 10;
                    }
                    // Get ruleset id
                    if(!isset($uploadedFile['ruleset_id'])||$uploadedFile['ruleset_id']=='') {
                        $pubs=$this->Publication->find('list',['fields'=>['id','ruleset_id']]);
                        $file['ruleset_id']=$pubs[$uploadedFile['publication_id']];
                    }
                    // Adds the file upload variables
                    if(!isset($file['file'])) {
                        unset($uploadedFile['file']);
                        $file+=$uploadedFile;
                    }
                    // Convert filetype to what is in db
                    if(stristr($file['type'],"pdf")) {
                        $file['type']='pdf';
                    } elseif(stristr($file['type'],"xml")) {
                        $file['type']='xml';
                    } elseif(stristr($file['type'],"text/plain")) {
                        $file['type']='txt';
                    }

                    // Add to database
                    $this->File->create();
                    if (!$this->File->save(['File'=>$file])) {
                        debug($this->File->validationErrors); die();
                    } else {
                        if($file['type']=="pdf") {
                            // If the file was a PDF then do the text extraction (default dpi 600 is default)
                            $this->File->pdf2txt($file['publication_id'],$file['filename'],$file['resolution']);
                        } elseif($file['type']=="xml") {
                            // If the file was XML then do the XSLT extraction
                            $this->Saxon->transform($file['filename'],$file['xslt'],'json',$file['publication_id']);
                        }
                    }
                    $fileid=$this->File->id;
                    $this->File->clear();
                    // Add to the activity log
                    $this->Activity->create();
                    $activity=[
                        'Activity'=>[
                            'user_id'=>$this->Auth->user('id'),
                            'file_id'=>$fileid,
                            'step_num'=>1,
                            'type'=>'upload',
                            'comment'=>''
                        ]
                    ];
                    $this->Activity->save($activity);
                }

                // Redirect to view
                if($this->request->is('ajax')) {
                    die('{ "status": "success" }');
                } elseif (!empty($this->request->params['requested'])) {
                    return true;
                } else {
                    if(count($files)==1) {
                        return $this->redirect('/files/view/'.$fileid);
                    } else {
                        return $this->redirect('/files/index');
                    }
                }
            }
        } else {
            $rulesets=$this->Ruleset->find('list',['fields'=>['id','name'],'order'=>'name']);
            $properties=$this->Property->find('list',['fields'=>['id','name'],'order'=>'name']);
            $pubs=$this->Publication->find('list',['fields'=>['id','title'],'order'=>'title','conditions'=>['phase'=>$phase]]);
            // Get the XSLT files currently uploaded to the VM
            $dir = new Folder(WWW_ROOT.'files/xslt');
            $xslts = $dir->find('.*\.xsl');
            $this->set('xslts',$xslts);
            $this->set('pubs',$pubs);
            $this->set('properties',$properties);
            $this->set('rulesets',$rulesets);
        }
    }

    /**
     * Download text file extracted from PDF
     * @param integer $id
     * @param string  $type
     */
    public function gettext($id,$type="text")
    {
        $file=$this->File->find('first',['conditions'=>['File.id'=>$id],'contain'=>['Publication']]);
        $pubid=$file['Publication']['id'];
        $name=substr($file['File']['filename'],0,-4);

        if($type=='text') {
            $append=".txt";
        } elseif($type=="keep") {
            $append='_keep.txt';
        } elseif($type=="trash") {
            $append="_trash.txt";
        } elseif($type=="empty") {
            $append="_empty.txt";
        } else {
            exit;
        }

        // Extract PDF if looking for the full text file
        if($type=="text") {
            $res=$file['File']['resolution'];
            $this->File->pdf2txt($pubid,$file['File']['filename'],$res);
        }

        // Output the file
        $filename=$name.$append;
        $path="files".DS."text".DS.$pubid.DS.$filename;
        header("Content-type: text/plain");
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        readfile($path);exit;
    }

    /**
     * Check the text file for how much the regexes match
     * @param $id
     */
    public function testregex($id)
    {
        $file=$this->File->find('first',['conditions'=>['File.id'=>$id],'contain'=>['Publication']]);
        $name=substr($file['File']['filename'],0,-4);
        $pubid=$file['Publication']['id'];
        $path="files".DS."text".DS.$pubid.DS.$name.".txt";
        $res=$file['File']['resolution'];
        $this->File->pdf2txt($pubid,$file['File']['filename'],$res);
        $text=file_get_contents($path);
        // Clean text file - SJC
        $type=mb_detect_encoding($text);
        if($type=="UTF-8") {
            $text=$this->Char->clean($text);
        }
        // Get rule regexes
        if(!empty($file['File']['ruleset_id'])) {
            $rset=$this->Ruleset->find('first',['conditions'=>['id'=>$file['File']['ruleset_id']]]);
            $rules=$rset['Rule'];
        } else {
            $con=['Publication.id'=>$pubid];
            $ctn=['Ruleset'=>['Rule']];
            $pub=$this->Publication->find('first',['conditions'=>$con,'contain'=>$ctn,'recursive'=>-1]);
            $rules=$pub['Ruleset']['Rule'];
        }
        $regexes=[];
        foreach($rules as $rule) {
            $step=$rule['RulesRuleset']['step'];
            $regexes[$step]=$this->Rule->regex($rule['id']);
        }
        ksort($regexes);
        // Split out the rows that match the rules from those that don't
        $tarray=explode("\n",$text);
        $keep=$disp=$trash=$empty=[];
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
                    $trash[$linenum]=$line;
                }
            }
        }

        // Save the arrays for download
        $path=WWW_ROOT."files".DS."text".DS.$pubid;
        $folder = new Folder($path,true,0777);
        $kfp=fopen(substr($path.DS.$file['File']['filename'],0,-4).'_keep.txt','w');
        fwrite($kfp,implode("\n",$keep));
        fclose($kfp);
        $tfp=fopen(substr($path.DS.$file['File']['filename'],0,-4).'_trash.txt','w');
        fwrite($tfp,implode("\n",$trash));
        fclose($tfp);
        $efp=fopen(substr($path.DS.$file['File']['filename'],0,-4).'_empty.txt','w');
        fwrite($efp,implode("\n",$empty));
        fclose($efp);
        // Send data to view
        $this->set('id',$id);
        $this->set('regexes',$regexes);
        $this->set('keep',$disp);
        $this->set('trash',$trash);
        $this->set('empty',$empty);
    }

    /**
     * Add a reference
     */
    public function refAdd()
    {
        if($this->request->is('post'))
        {
            $uploadedFile=array();
            if (!empty($this->request->params['requested'])) {
                $uploadedFile=$this->request->params['File'];
            }else{
                $uploadedFile=$this->request->data['File'];
            }
            $xml=simplexml_load_file($uploadedFile['file']['tmp_name']);
            $refs=(array)$xml->Series->Book->Chapter->ChapterBackmatter->Bibliography; //get to the citation
            if(isset($refs['BibSection']))
            {
                $refs['Citation']=array();
                foreach($refs['BibSection'] as $section){
                    foreach($section->Citation as $cit){
                        $refs['Citation'][]=$cit;
                    }
                }
            }
            foreach($refs['Citation'] as $i=>$targetRef) {
                $this->Reference->clear();
                $reference = array('Reference' => array());
                $reference['Reference']['sid'] = (string)$targetRef->CitationNumber;
                if(!isset($targetRef->BibArticle->BibAuthorName)){
                    $reference['Reference']['title'] = (string)$targetRef->BibUnstructured;
                }else {
                    $reference['Reference']['journal'] = (string)$targetRef->BibArticle->JournalTitle;
                    $reference['Reference']['title'] = (string)$targetRef->BibArticle->ArticleTitle;
                    $reference['Reference']['year'] = (string)$targetRef->BibArticle->Year;
                    $reference['Reference']['volume'] = (string)$targetRef->BibArticle->VolumeID;
                    $reference['Reference']['startpage'] = (string)$targetRef->BibArticle->FirstPage;
                    $reference['Reference']['endpage'] = (string)$targetRef->BibArticle->LastPage;
                    $reference['Reference']['issue'] = "";
                    if (isset($targetRef->BibArticle->Issue)) {
                        $reference['Reference']['issue'] = (string)$targetRef->BibArticle->Issue;
                    }
                    $reference['Reference']['authors'] = "";
                    foreach ($targetRef->BibArticle->BibAuthorName as $author) {
                        $reference['Reference']['authors'] .= (string)$author->Initials;
                        $reference['Reference']['authors'] .= " " . (string)$author->FamilyName . ", ";
                    }
                    $reference['Reference']['authors'] = substr($reference['Reference']['authors'], 0, strlen($reference['Reference']['authors']) - 2); //remove trailing comma
                }
                $result = $this->Reference->find('all', ['conditions' => ['sid' => $reference['Reference']['sid']]]);
                if(count($result)>0){
                    echo "We have more than one of this SID";
                    $error=arraY('Error'=>array('errorType'=>3,'errorText'=>'Duplicate Ref','value'=>$reference['Reference']['sid'],'file'=>0));
                    $this->Errors->create();
                    $this->Errors->save($error);
                }

                $result = $this->Reference->find('all', ['conditions' => ['title' => $reference['Reference']['title'], 'year' => $reference['Reference']['year']]]);
                $match = false;
                if (!empty($result)) {
                    foreach ($result as $ref) { //check all the references that we got back and see if all of their values match all the values we retrieved from the file
                        $match = true;
                    //    echo "<br> Checking  ".$ref['Reference']['id']."<br><br>";
                        foreach ($reference['Reference'] as $key => $value) {
                            if ($key == "sid") {
                                continue;
                            }
                          //  echo "KEY[".$key."]".$ref['Reference'][$key]." == ".$value.'<br>';
                            if (isset($ref['Reference'][$key]) && $ref['Reference'][$key] !== $value) {
                                $match = false;
                                continue 2;
                            }
                        }
                        if ($match === true) {
                            $this->Reference->id = $ref['Reference']['id'];
                         //   echo "Reference exist at ".$this->Reference->id."<br>";
                            break;
                        }
                    }
                }
                if ($match === false) {
                    echo "Reference was not found, creating<br>";
                    $this->Reference->create();
                }else{
                    echo "Reference exist at ".$this->Reference->id."<br>";
                }

                $result=$this->Reference->save($reference);
                if(!$result){
                    debug($this->validationErrors);
                    echo "Failed to save ".$reference['Reference']['sid']."<br>";
                }else{
                    echo "Saved ".$reference['Reference']['sid']."<br>";
                }
            }
            die("DONE");


        } else {
            $pubs=$this->Publication->find('list',['fields'=>['id','title']]);
            $this->set('pubs',$pubs);
        }
    }

    /**
     * View a file
     * @param integer $id
     */
    public function view($id)
    {
        $c=['Publication','Propertytype','TextFile'=>['fields'=>['id','title'],'order'=>'sysnum','conditions'=>['NOT'=>['status'=>'retired']]],'Dataset','Ruleset']; // Must have id field
        $data=$this->File->find('first',['conditions'=>['File.id'=>$id],'contain'=>$c,'recursive'=>-1]);
        $this->set('data',$data);
    }

    /**
     * Update a file
     */
    public function update($id)
    {
        if(!empty($this->request->data)) {
            //debug($this->request->data);exit;
            $this->File->id=$id;
            $this->File->save($this->request->data);
            $this->redirect(['action' => 'index']);
        } else {
            $data=$this->File->find('first',['conditions'=>['File.id'=>$id]]);
            $this->set('data',$data);
            $pubs=$this->Publication->find('list',['fields'=>['id','title']]);
            $this->set('pubs',$pubs);
            $this->set('id',$id);
        }
    }

    /**
     * Update a field in the files table (AJAX call)
     * @param int $id
     */
    public function updatefield($id=0)
    {
        $field=$this->data['field'];
        $value=$this->data['value'];

        if($id==0||$field==""||$value=="") {
            echo false;exit;
        } else {
            $this->File->id=$id;
            if($this->File->saveField($field,$value)) {
                echo true;exit;
            } else {
                echo false;exit;
            }
        }
    }

    /**
     * Delete a file
     * @param integer $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->File->delete($id);
        return $this->redirect('/files/index');
    }

    /**
     * List the files by publication
     */
    public function index()
    {
        $files=$this->File->find('list',['fields'=>['File.id','File.title','Publication.title'],'contain'=>['Publication'],'recursive'=>-1]);
        $this->set('pubs',$files);
    }
    
    /**
     * Count all the files
     * @return mixed
     */
    public function totalfiles()
    {
        $data=$this->File->find('count');
        return $data;
    }

}

