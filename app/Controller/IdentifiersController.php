<?php

/**
 * Class IdentifiersController
 * Actions related to dealing with units
 * @author Stuart Chalk <schalk@unf.edu>
 *
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

    /**
     * Remove abandoned identifiers (no substance)
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
        echo count($subs)."<br />";exit;
    }

    
    public function classy()
	{
		$key='LVTYICIALWPMFW-UHFFFAOYSA-N';
		$json=file_get_contents('http://classyfire.wishartlab.com/entities/'.$key.'.json');
		$classy=json_decode($json,true);
		$kingdom=$classy['kingdom']['name'];
		if($kingdom=='Inorganic compounds') {
			$superclass=$classy['superclass']['name'];
			if($superclass=='Homogeneous metal compounds') {
				// elements!
				$type='element';$subtype='';
			} else {
				$type='compound';$subtype='inorganic compound';
			}
			
		} elseif($kingdom=='Organic compounds') {
			$type='compound';$subtype='organic compound';
		}
		debug($kingdom);exit;
	}
}