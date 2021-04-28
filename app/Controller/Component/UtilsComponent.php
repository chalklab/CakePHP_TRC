<?php
App::uses('Component', 'Controller');

class UtilsComponent extends Component {

    /**
     * Get PDF Version from PDF file
     * @param $filename
     * @return mixed
     */
    public function pdfVersion($filename) {
        $fp = @fopen($filename, 'rb');

        if (!$fp) {
            return 0;
        }

        /* Reset file pointer to the start */
        fseek($fp, 0);

        /* Read 20 bytes from the start of the PDF */
        preg_match('/\d\.\d/',fread($fp,20),$match);

        fclose($fp);

        if (isset($match[0])) {
            return $match[0];
        } else {
            return 0;
        }
    }

	/**
	 * convert array of strings to uppercase first
	 * @param $array
	 * @return array
	 */
    public function ucfarray($array): array
    {
        $oarray=[];
        for($x=0;$x<count($array);$x++) {
            $oarray[$array[$x]]=ucfirst($array[$x]);
        }
        return $oarray;
    }

    /**
     * Export SQL
     * @param $class
     * @param $id
     * @param $output
     * @return mixed;
     */
    public function sql($class,$id,&$output)
    {
        $tablearray=[];
        $table=Inflector::tableize($class);

        if(is_array($id)) {
            $idstr=implode(",",$id);
            $cmd='/usr/local/bin/mysqldump --opt --compact --user=springer --password=springer trc '.$table.'  --where="id in ('.$idstr.')"';
        } else {
            $cmd='/usr/local/bin/mysqldump --opt --compact --user=springer --password=springer trc '.$table.'  --where="id='.$id.'"';
        }
        exec($cmd,$output,$return);
        if($return==0) {
            return true;
        } else {
            return $return;
        }
    }

}
