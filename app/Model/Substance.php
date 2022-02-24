<?php

/**
 * Class Substance
 * model for the substances table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Substance extends AppModel
{
    // relationships to other tables
	public $hasMany=[
        'Identifier'=> [
            'foreignKey' => 'substance_id',
            'dependent' => true
        ],
		'Chemical'=> [
			'foreignKey' => 'substance_id',
			'dependent' => true
		]
    ];
    public $hasAndBelongsToMany = ['System'];

	// create additional 'virtual' fields built from real fields
	public $virtualFields=[
    	'first' => 'UPPER(SUBSTR(Substance.name,1,1))',
		'namekey'=>"CONCAT(Substance.name,'|',Substance.inchikey)",
	];

	/**
	 * function to add a new substance if it does not already exist
	 * @param array $data
	 * @return int
	 * @throws
	 */
	public function add(array $data): int
	{
		return $this->addentry('Substance',$data);
	}

	/**
     * get the metadata for a new compound from PubChem/ChemSpider
     * @param array $c
     * @param bool|false $show
	 * @throws
     */
    public function meta(array $c, bool $show=false)
    {
        $Chemical=ClassRegistry::init('Pubchem.Compound'); //load the Pubchem model
        $Rdf=ClassRegistry::init('Chemspider.Rdf'); //load the Chemspider model
        $Substance=ClassRegistry::init('Substance'); //load the Substance model
        $Identifier=ClassRegistry::init('Identifier');//load the Identifier model
        $i=$c['Identifier'];$s=$c['Substance'];
        // Search PubChem
        $cid=$Chemical->cid('name',$i[0]['value']);
        if ($cid) {
            // Add the PubChem ID
            $test=$Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>'pubchemId'],'recursive'=>-1]);
            if(empty($test)) {
                $Identifier->add(['substance_id'=>$s['id'],'type'=>'pubchemId','value'=>$cid]);
            }
            if($show) {
                echo "<h3>".$s['name']." (PubChem)</h3>";
                echo "<ul>";
            }
            $ps=['iupacname'=>'IUPACName','inchi'=>'InChI','inchikey'=>'InChIKey','mw'=>'MolecularWeight'];
            foreach($ps as $t=>$p) {
                if($t=='mw') {
                    // Check to see if the value is already in the DB
                    $test=$Substance->find('list',['fields'=>['id',$t],'conditions'=>['id'=>$s['id']]]);
                    if($test[$s['id']]==''||$test[$s['id']]==0||is_null($test[$s['id']])) {
                        $meta=$Chemical->property($p,$cid);
                        if(isset($meta[$p])) {
                            if($show) {
                                echo "<li>" . $p . ": " . $meta[$p] . "</li>";
                            }
                            $Substance->save(['id'=>$s['id'],$t=>$meta[$p]]);
                            $Substance->clear();
                        }
                    }
                } else {
                    // Check to see if the value has already been added
                    $test=$Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>$t],'recursive'=>-1]);
                    if(empty($test)) {
                        $meta=$Chemical->property($p,$cid);
                        if(isset($meta[$p])) {
                            if($show) {
                                echo "<li>".$p.": ".$meta[$p]."</li>";
                            }
                            $Identifier->add(['substance_id'=>$s['id'],'type'=>$t,'value'=>$meta[$p]]);
                        }
                    }
                }
            }
            if($show) {
                echo "</ul>";
            }
        }
        // Search ChemSpider
        $meta=$Rdf->search($i[0]['value']);
        if($meta) {
            if($show) {
                echo "<h3>" . $s['name'] . " (ChemSpider)</h3>";
                echo "<ul>";
            }
            $ps=['chemspiderId'=>'id','iupacname'=>'name','smiles'=>'smiles','inchi'=>'inchi','inchikey'=>'inchikey'];
            foreach($ps as $t=>$p) {
                if(isset($meta[$p])) {
                    if($show) {
                        echo "<li>" . $p . ": " . $meta[$p] . "</li>";
                    }
                    $test=$Identifier->find('first',['conditions'=>['substance_id'=>$s['id'],'type'=>$t],'recursive'=>-1]);
					if(empty($test)) {
						$Identifier->add(['substance_id'=>$s['id'],'type'=>$t,'value'=>$meta[$p]]);
					}
                }
            }
            if($show) {
                //debug($meta);
                echo "</ul>";
            }
        }
        // Cleanup
        if($show) {
            echo "<h3>Cleanup</h3>";
            echo "<ul>";
        }
        $pcid=$Identifier->find('list',['fields'=>['substance_id','value'],'conditions'=>['substance_id'=>$s['id'],'type'=>'pubchemId'],'recursive'=>-1]);
        // Use Inchikey to find PubChemId
        if(empty($pcid)) {
            $cid=$Chemical->cid('name',$meta['inchikey']);
            if($cid) {
                $Identifier->add(['substance_id'=>$s['id'],'type'=>'pubchemId','value'=>$cid]);
            } else {
                $cid='Not found on PubChem';
            }
        } else {
            $cid=$pcid[$s['id']];
        }
        if($show) {
            echo "<li>CID: " . $cid . "</li>";
        }
        if(is_numeric($cid)) {
            $mw=$Substance->find('list',['fields'=>['id','mw'],'conditions'=>['id'=>$s['id']]]);
            if($mw[$s['id']]=='') {
                // Use inchikey from ChemSpider search to get molweight from PubChem
                $mw=$Chemical->property('MolecularWeight',$cid);
                if($mw) {
                    $Substance->save(['id'=>$s['id'],'mw'=>$mw['MolecularWeight']]);
                    $Substance->clear();
                }
                if($show) {
                    //debug($mw);exit;
                    echo "<li>MW: ".$mw['MolecularWeight']."</li>";
                }
            }
            if($show) {
                echo "</ul>";
            }
        }
        if($show) {
            echo "</ul>";
        }
        exit;
    }

	/**
	 * molmass by Christoph Gohlke (https://www.lfd.uci.edu/~gohlke/molmass/)
	 * scrapes the average mass from the webpage
	 */
	public function ucimw($formula=null)
	{
		if(!is_null($formula)) {
			// https://www.lfd.uci.edu/~gohlke/molmass/?q=C6H6
			$contents = file_get_contents("https://www.lfd.uci.edu/~gohlke/molmass/?q=".$formula);
			preg_match('/Average mass<\/strong>: ([\d\.]*)<\/p>/',$contents,$match);
			return $match[1];
		} else {
			return false;
		}
	}

}
