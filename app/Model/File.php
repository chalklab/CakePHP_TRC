<?php

/**
 * Class File
 * File model Testing
 */
class File extends AppModel
{
    public $format=0;

    public $belongsTo = ['Publication','Propertytype','Ruleset'];

    /**
     * Link text_files and dataset as dependent so they get deleted when the file does
     * @var array
     */
    public $hasMany = [
        'TextFile'=>[
            'foreignKey' => 'file_id',
            'dependent' => true
        ],
        'Dataset'=>[
            'foreignKey' => 'file_id',
            'dependent' => true
        ],
        'Activity'=>[
            'foreignKey' => 'file_id',
            'dependent' => true
        ]
    ];

    /**
     * Extract text from PDF using PDFtoText
     * @param integer $pubid
     * @param string $pdfname
     * @param integer $res
     * @return boolean
     */
    public function pdf2txt($pubid,$pdfname,$res=600)
    {
        $Char=ClassRegistry::init('Char');

        // Extract and save pdf text using PDFtoText
        $name=substr($pdfname,0,-4);

        // Get local absolute path for pdftotext
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $pdfToTextPath = Configure::read("pdftotextPath.windows");
        } elseif (PHP_OS=="Linux") {
            $pdfToTextPath=Configure::read("pdftotextPath.linux");
        } elseif (PHP_OS=="FreeBSD") {
            $pdfToTextPath=Configure::read("pdftotextPath.freebsd");
        } else {
            $pdfToTextPath=Configure::read("pdftotextPath.mac");
        }

        // Extract the text
        $pdfpath=WWW_ROOT."files".DS."pdf".DS.$pubid.DS.$name.".pdf";
        $execpath=$pdfToTextPath.' -enc UTF-8 -layout -r '.$res.' "'.$pdfpath.'" -';
        $text=shell_exec($execpath);

        //debug($execpath);exit;

        // Clean text file of annoying unicode chars - SJC
        $type=mb_detect_encoding($text);
        if($type=="UTF-8") {
            $text=$Char->clean($text);
        }

        // Save the TEXT file
        $folder = new Folder(WWW_ROOT."files".DS."text".DS.$pubid,true,0777);
        $textpath = WWW_ROOT."files".DS."text".DS.$pubid.DS.$name.".txt";
        file_put_contents($textpath, $text);

        // Check that the file was saved
        return file_exists($textpath);
    }

    /**
     * Get path to the pdftotext for the server
     * @return string
     */
    public function pdf2txtpath()
    {
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $path = Configure::read("pdftotextPath.windows");
        } elseif (PHP_OS=="Linux") {
            $path=Configure::read("pdftotextPath.linux");
        } elseif (PHP_OS=="FreeBSD") {
            $path=Configure::read("pdftotextPath.freebsd");
        } else {
            $path=Configure::read("pdftotextPath.mac");
        }
        return $path;
    }

    /**
     * function getCode
     * Gets the property type code from a file that has already been transferred to the pdf folder
     * @param $filename: The name of the file to extract the property type code from
     * @param $publicationID: ID of the publication in string format
     * @return bool|string $propertyID: returned the found property id if it exist.
     * @throws Exception
     */
    public function getCode($filename,$publicationID){
        $fileToExtract=WWW_ROOT.'files'.DS.'pdf'.DS.$publicationID.DS.$filename;// find the path to the file name
        if (file_exists($fileToExtract)) {
            if (strtoupper(substr(PHP_OS,0,3))==='WIN') {
                $pdfToTextPath = Configure::read("pdftotextPath.windows"); //save path to the pdftotext for the server
            } elseif (PHP_OS=="Linux") {
                $pdfToTextPath=Configure::read("pdftotextPath.linux");
            } elseif (PHP_OS=="FreeBSD") {
                $pdfToTextPath=Configure::read("pdftotextPath.freebsd");
            } else {
                $pdfToTextPath=Configure::read("pdftotextPath.mac");
            }
            $status=0;
            $this->format = 0;
            if(pathinfo($filename, PATHINFO_EXTENSION)=="pdf") {
                $str = shell_exec($pdfToTextPath . ' -enc UTF-8 -layout -r 300 "' . $fileToExtract . '" -'); //run the extraction
            }else{
                $str=file_get_contents($fileToExtract);
            }
            preg_match("!Property Type: \[(\w*)\]!",$str,$matches); //general match
            if(empty($matches)){
                preg_match("!Property Code (\w*)!",$str,$matches); //match for file that uses code instead of type
                if(!empty($matches)) {
                    $this->format = 1;
                }
            }
            if(empty($matches)){
                preg_match("!Property Type (\w*)!",$str,$matches); //match for file that uses code instead of type
                if(!empty($matches)) {
                    preg_match("!(Data Set|Dataset) (\w*)!",$str,$datasetStr); //match for file that uses code instead of type
                    if(!empty($datasetStr)){
                        $this->format = 3;
                    }else {
                        $this->format = 2;
                    }
                }
            }
            if(!isset($matches[1])){
                return false;
            }
            return trim($matches[1]);
        } else {
            throw new Exception($filename.": File Not Found");
        }
    }

    /**
     * Clean file (remove all data related to file but NOT the file itself)
     * @param $id
     * @return boolean
     */
    public function clean($id)
    {
        $Tfile=ClassRegistry::init('TextFile');
        $Set=ClassRegistry::init('Dataset');
        $Report=ClassRegistry::init('Report');

        $tfiles=$Tfile->find('list',['fields'=>['id','title'],'conditions'=>['file_id'=>$id]]);
        foreach($tfiles as $tfid=>$title) {
            $Tfile->delete($tfid);
        }
        $dfiles=$Set->find('list',['fields'=>['id','report_id'],'conditions'=>['file_id'=>$id]]);
        foreach($dfiles as $dsid=>$rid) {
            $Set->delete($dsid);
            $Report->delete($rid);
        }
        // Update the file
        $this->id=$id;
        $this->saveField("status","uploaded");
        return true;
    }
}