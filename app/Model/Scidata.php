<?php

/**
 * Class Scidata
 * Scidata model
 */
class Scidata extends AppModel
{

	public $useTable=false;

	public $path=null;
	public $contexts=["https://stuchalk.github.io/scidata/contexts/scidata.jsonld"];
	public $nspaces=[
		'sdo'=>'https://stuchalk.github.io/scidata/ontology/scidata.owl#',
		'dc'=>'http://purl.org/dc/terms/',
		'qudt'=>'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
		'xsd'=>'http://www.w3.org/2001/XMLSchema#'];
	public $id=null;
	public $generatedat=null;
	public $version=null;
	public $graphid=null;
	public $uid=null;
	public $base=null;
	public $meta=null;
	public $authors=null;
	public $related=null;
	public $keywords=null;
	public $startdate=null;
	public $permalink=null;
	public $toc=null;
	public $ids=null;
	public $report=null;
	public $discipline=null;
	public $subdiscipline=null;
	public $facets=null;
	public $aspects=null;
	public $data=null;
	public $dataseries=null;
	public $datagroup=null;
	public $datapoint=null;
	private $cpds=[];
	private $syss=[];
	private $chms=[];
	private $sets=[];
	private $sers=[];
	private $pnts=[];
	private $cnds=[];
	private $rels=[];
	private $scnds=[];
	private $sttgs=[];
	public $sources=null;
	public $rights=null;
	public $errors=null;
	public $output=null;
	public $ontlinks=null;
	public $intlinks=null;
	public $sysrows=null;

	/**
	 * Class Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$output=[];
		$output['@context']=[];
		$output['@id']="";
		$output['generatedAt']="";
		$output['version']=1;
		$graph=[];
		$graph['@id']="";
		$graph['@type']="sdo:scientificData";
		$graph['uid']="";
		$graph['title']="";
		$graph['authors']=[];
		$graph['description']="";
		$graph['publisher']="";
		$graph['startdate']="";
		$graph['permalink']="";
		$graph['keywords']=[];
		$graph['related']=[];
		$graph['toc']=[];
		$graph['ids']=[];
		$graph['report']=[];
		$graph['scidata']=[];
		$graph['sources']=[];
		$graph['rights']=[];
		$output['@graph']=$graph;

		$this->output=$output;
	}

	// Getters

	/**
	 * Get path
	 */
	public function path() {
		if(is_null($this->path)) {
			return false;
		} else {
			return $this->path;
		}
	}

	/**
	 * Get base
	 */
	public function base() {
		if(is_null($this->base)) {
			return false;
		} else {
			return $this->base;
		}
	}

	/**
	 * Get meta
	 * @return mixed
	 */
	public function meta() {
		if(is_null($this->meta)) {
			return false;
		} else {
			return $this->meta;
		}
	}

	/**
	 * Get facets
	 * @return mixed
	 */
	public function getfacets() {
		if(is_null($this->facets)) {
			return false;
		} else {
			return $this->facets;
		}
	}

	// Setters

	/**
	 * Set the contexts
	 * @param $value
	 * @return bool
	 */
	public function setcontexts($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("contexts","array",$value,'merge');
		}
	}

	/**
	 * Set the nspaces
	 * @param $value
	 * @return bool
	 */
	public function setnspaces($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("nspaces","array",$value,'merge');
		}
	}

	/**
	 * Set the id
	 * @param $value
	 * @return bool
	 */
	public function setid($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("id","string",$value);
		}
	}

	/**
	 * Set graph id
	 * @param $value
	 * @return bool
	 */
	public function setgraphid($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("graphid","string",$value);
		}
	}

	/**
	 * Set generatedAt date
	 * @param $value
	 * @return bool
	 */
	public function setgenat($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("generatedat","string",$value);
		}
	}

	/**
	 * Set version
	 * @param $value
	 * @return bool
	 */
	public function setversion($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("version","integer",$value);
		}
	}

	/**
	 * Set the path
	 * @param $value
	 * @return bool
	 */
	public function setpath($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("path","string",$value);
		}
	}

	/**
	 * Set the base
	 * @param $value
	 * @return bool
	 */
	public function setbase($value=null) {
		if($value==null) {
			return false;
		} else {
			if(is_null($this->path)) {
				return $this->setter("base","string",$value);
			} else {
				return $this->setter("base","string",$this->path.$value);
			}
		}
	}

	/**
	 * Set the meta
	 * @param $value
	 * @return bool
	 */
	public function setmeta($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("meta","array",$value);
		}
	}

	/**
	 * Set the uid
	 * @param $value
	 * @return bool
	 */
	public function setuid($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("uid","string",$value);
		}
	}

	/**
	 * Set the related array
	 * @param $value
	 * @return bool
	 */
	public function setrelated($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("related","array",$value);
		}
	}

	/**
	 * Set the keywords array
	 * @param $value
	 * @return bool
	 */
	public function setkeywords($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("keywords","array",$value);
		}
	}

	/**
	 * Set the toc array
	 * @param $value
	 * @return bool
	 */
	public function settoc($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("toc","array",$value);
		}
	}

	/**
	 * Set the report array
	 * @param $value
	 * @return bool
	 */
	public function setreport($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("report","array",$value);
		}
	}

	/**
	 * Set authors
	 * @param null $value
	 * @return bool
	 */
	public function setauthors($value=null)
	{
		if($value==null) {
			return false;
		} else {
			$authors=[];
			if(!is_array($value)) {
				if(stristr($value,'{')) {
					$authors=json_decode($value,true);
				} elseif(stristr($value,', ')) {
					$authors=explode(", ",$value);
				} else {
					$authors[0]=$value;
				}
			} else {
				$authors=$value;
			}
			return $this->setter("authors","array",$authors);
		}
	}

	/**
	 * Set the startdate
	 * @param $value
	 * @return bool
	 */
	public function setstartdate($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("startdate","string",$value);
		}
	}

	/**
	 * Set the permalink
	 * @param $value
	 * @return bool
	 */
	public function setpermalink($value=null) {
		if($value==null) {
			return false;
		} else {
			if(is_null($this->path)) {
				return $this->setter("permalink","string",$value);
			} else {
				return $this->setter("permalink","string",$this->path.$value);
			}
		}
	}

	/**
	 * Set the discipline
	 * @param $value
	 * @return bool
	 */
	public function setdiscipline($value=null) {
		if($value==null) {
			return false;
		} else {
			if(stristr($value,':')) {
				$ids=$this->ids;
				$ids[]=$value;
				$this->ids=$ids;
			}
			return $this->setter("discipline","string",$value);
		}
	}

	/**
	 * Set the subdiscipline
	 * @param $value
	 * @return bool
	 */
	public function setsubdiscipline($value=null) {
		if($value==null) {
			return false;
		} else {
			if(stristr($value,':')) {
				$ids=$this->ids;
				$ids[]=$value;
				$this->ids=$ids;
			}
			return $this->setter("subdiscipline","string",$value);
		}
	}

	/**
	 * Set the aspects
	 * @param null $value
	 * @return bool
	 */
	public function setaspects($value=null)
	{
		if($value==null) {
			return false;
		} else {
			return $this->setter("aspects","array",$value);
		}
	}

	/**
	 * Set the facets
	 * @param null $value
	 * @return bool
	 */
	public function setfacets($value=null)
	{
		if($value==null) {
			return false;
		} else {

			return $this->setter("facets","array",$value);
		}
	}

	/**
	 * Set the sysrows (for a document with multiple systems, the rows of data that are for each system)
	 * @param null $value
	 * @return bool
	 */
	public function setsysrows($value=null)
	{
		if($value==null) {
			return false;
		} else {
			return $this->setter("sysrows","array",$value);
		}
	}

	/**
	 * Set the data
	 * @param null $value
	 * @return bool
	 */
	public function setdata($value=null)
	{
		if($value==null) {
			return false;
		} else {
			return $this->setter("data","array",$value);
		}
	}

	/**
	 * Set a dataseries
	 * @param null $value
	 * @return bool
	 */
	public function setdataseries($value=null)
	{
		if($value==null) {
			return false;
		} else {
			return $this->setter("dataseries","array",$value);
		}
	}

	/**
	 * Set a datagroup
	 * @param null $value
	 * @return bool
	 */
	public function setdatagroup($value=null)
	{
		if($value==null) {
			return false;
		} else {
			return $this->setter("datagroup","array",$value);
		}
	}

	/**
	 * Set a datapoint
	 * @param null $value
	 * @param string $mode (append|replace|clear)
	 * @return bool
	 */
	public function setdatapoint($value=null,$mode='append')
	{
		if($value==null||!is_array($value)) {
			return false;
		} else {
			return $this->setter("datapoint","array",$value,$mode);
		}
	}

	/**
	 * Set the sources
	 * @param $value
	 * @return bool
	 */
	public function setsources($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("sources","array",$value);
		}
	}

	/**
	 * Set the rights
	 * @param $value
	 * @return bool
	 */
	public function setrights($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("rights","array",$value);
		}
	}

	/**
	 * Set the ontology links
	 * @param $value
	 * @return bool
	 */
	public function setontlinks($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("ontlinks","array",$value);
		}
	}

	/**
	 * Set the internal links
	 * @param $value
	 * @return bool
	 */
	public function setintlinks($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("intlinks","array",$value);
		}
	}

	/**
	 * Generic setter method
	 * @param $prop
	 * @param $type
	 * @param $value
	 * @param string $mode (append|replace|clear)
	 * @return bool
	 */
	private function setter($prop,$type,$value,$mode='replace') {
		// check datatype
		if($type=="array") {
			if(!is_array($value)) {
				return false;
			}
		} elseif($type=="string") {
			if(!is_string($value)) {
				return false;
			}
		}
		// set property
		$current=$this->$prop;
		if($mode=='append') {
			if($type=='array') {
				if(empty($current)) {
					$this->$prop=['1'=>$value];
				} else {
					$lastkey=max(array_keys($current));
					$this->$prop=$current+[($lastkey+1)=>$value];
				}
			} elseif($type=="string") {
				$this->$prop=$current.$value;
			}
		} elseif($mode=='merge') {
			// merges the value an existing array
			if(empty($current)) {
				$this->$prop=['1'=>$value];
			} else {
				if(is_array($current)&&is_array($value)) {
					$this->$prop=array_merge($current,$value);
				} elseif(is_array($current)&&!is_array($value)) {
					$current[]=$value;
				}
			}
		} elseif($mode=='replace') {
			$this->$prop=$value;
		} elseif($mode=='clear') {
			$this->$prop=null;
		}

		// check property set
		if($this->$prop==$value) {
			return true;
		} else {
			return false;
		}
	}

	// Methods

	/**
	 * create output and return
	 * @return mixed
	 */
	public function asarray()
	{
		$ppty=ClassRegistry::init('Property');
		$olinks=$this->ontlinks;
		$output=$this->output;
		$graph=$output["@graph"];
		$output['@context']=array_merge(
			$this->contexts,[
			$this->nspaces,
			['@base'=>$this->base]]);

		// id
		if(!is_null($this->id)) {
			$output["@id"]=$this->id;
		} else {
			unset($output["@id"]);
		}

		// generatedAt
		if(!is_null($this->generatedat)) {
			$output["generatedAt"]=$this->generatedat;
		} else {
			unset($output["generatedAt"]);
		}

		// version
		if(!is_null($this->version)) {
			$output["version"]=$this->version;
		} else {
			unset($output["version"]);
		}

		// graphid
		if(!is_null($this->graphid)) {
			$graph["@id"]=$this->graphid;
		} else {
			unset($graph["@id"]);
		}

		// uid
		if(!is_null($this->uid)) {
			$graph["uid"]=$this->uid;
		} else {
			unset($output["uid"]);
		}

		// meta
		if(!is_null($this->meta)) {
			if(isset($this->meta['title'])) 		{ $graph['title']=$this->meta['title']; }
			if(isset($this->meta['description'])) 	{ $graph['description']=$this->meta['description']; }
			if(isset($this->meta['publisher'])) 	{ $graph['publisher']=$this->meta['publisher']; }
		} else {
			unset($output["meta"]);
		}

		// related
		if(!is_null($this->related)) {
			$graph["related"]=$this->related;
		} else {
			unset($output["related"]);
		}

		// keywords
		if(!is_null($this->keywords)) {
			$graph["keywords"]=$this->keywords;
		} else {
			unset($output["keywords"]);
		}

		// toc
		if(!is_null($this->toc)) {
			$graph["toc"]=$this->toc;
		} else {
			unset($output["toc"]);
		}

		// report
		if(!is_null($this->report)) {
			$graph["report"]=$this->report;
		} else {
			unset($output["report"]);
		}

		// authors
		if(!is_null($this->authors)) {
			foreach ($this->authors as $idx=>$au) {
				$graph['authors'][]=['@id'=>'author/'.($idx+1).'/','@type'=>'dc:creator','name'=>$au];
			}
		} else {
			unset($output["authors"]);
		}

		// startdate
		if(!is_null($this->startdate)) {
			$date=$this->startdate;
			// work out format and convert to UTC
			if(!stristr($date,"T")) {
				if(is_numeric($date)) {
					$date=date(DATE_ATOM,$date);
				} elseif(stristr($date,",")) {
					$date=date(DATE_ATOM,strtotime($date));
				}
			}
			$graph["startdate"]=$date;
		} else {
			unset($output["startdate"]);
		}

		// permalink
		if(!is_null($this->permalink)) {
			$output["permalink"]=$this->permalink;
		} else {
			unset($output["permalink"]);
		}

		// SciData
		$sci=[];$rels=[];

		$sci['@id']="scidata";
		$sci['@type']="sdo:scientificData";
		$graph['toc'][]=$sci['@type'];
		$sci['discipline']=$this->discipline;
		$sci['subdiscipline']=$this->subdiscipline;
		if(!empty($this->aspects)) { $sci['methodology']=[]; }
		if(!empty($this->facets)) { $sci['system']=[]; }
		if(!empty($this->dataseries)||!empty($this->datagroup)||!empty($this->datapoint)) { $sci['dataset']=[]; }

		// methodology
		if(!empty($this->aspects)) {
			$meth=[];
			$meth['@id']='methodology/';
			$meth['@type']='sdo:methodology';
			$meth['aspects']=[];
			$rels['methodology']=[];
			foreach($this->aspects as $type=>$aspgrp) {
				foreach($aspgrp as $idx=>$aspect) {
					$asp=[];$ns="";
					if(stristr($type,":")) {
						list($ns,$type)=explode(":",$type);
						$ns.=":";
					}
					$asp["@id"]=$type."/".$idx.'/';
					$asp["@type"]=$ns.$type;
					$meth['aspects'][]=array_merge($asp,$aspect);
					// add to toc
					$graph['toc'][]=$asp['@type'];
					// add methodology links
					$rels['methodology'][$type][$idx]=$asp['@id'];
				}
			}
			$sci['methodology']=$meth;
		}

		// system
		if(!empty($this->facets)) {
			$sys=[];
			$sys['@id']='system/';
			$sys['@type']='sdo:system';
			$sys['facets']=[];$condrels=[];
			$rels['system']=[];
			foreach($this->facets as $type=>$facgrp) {
				$fac=[];$ns="";
				if(stristr($type,":")) {
					list($ns,$type)=explode(":",$type);
					$ns.=":";
				}
				foreach($facgrp as $idx=>$facet) {
					$fac["@id"]=$type."/".$idx.'/';
					$fac["@type"]=$ns.$type;
					if($type=='condition') {
						$facet=$this->makedata($facet,$type,$idx,$condrels,$graph);
					} else {
						//debug($facet);
						foreach($facet as $label1=>$items1) {
							if(substr($label1,-1)=='s') {
								$sublabel=substr($label1,0,-1);
							} else {
								$sublabel=$label1;
							}
							//debug($items1);
							if(is_array($items1)) {
								foreach($items1 as $idx1=>$item1) {
									//debug($item1);
									if(is_array($item1)) {
										//debug($idx);debug($item);exit;
										foreach($item1 as $label2=>$items2) {
											if(substr($label2,-1)=='s') {
												$sublabel2=substr($label2,0,-1);
											} else {
												$sublabel2=$label2;
											}
											if(is_array($items2)) {
												foreach($items2 as $idx2=>$item2) {
													$id2=$sublabel2.'/'.($idx2+1).'/';
													$itype2='sdo:'.$sublabel2;
													$item2=["@id"=>$id2,"@type"=>$itype2]+$item2;
													$items2[$idx2]=$item2;
												}
											}
											$item1[$label2]=$items2;
										}
										$id1=$sublabel.'/'.($idx1+1).'/';
										$itype1='sdo:'.$sublabel;
										$item1=["@id"=>$id1,"@type"=>$itype1]+$item1;
										$items1[$idx1]=$item1;
									}
								}
								$facet[$label1]=$items1;
							}
						}
					}
					$sys['facets'][]=array_merge($fac,$facet);
					// add to toc
					$graph['toc'][]=$fac['@type'];
					// add to condition relative links
					$condrels['system'][$type][$idx]=$fac['@id'];
				}
			}
			$sci['system']=$sys;
		}

		// dataset
		$set=[];
		if(!empty($this->dataseries)||!empty($this->datagroup)||!empty($this->datapoint)) {
			$set["@id"]='dataset/1/';
			$set["@type"]='sdo:dataset';
			if(!empty($this->datapoint)) {
				// start the point total count at the # of individual datapoints
				$pnttot=count($this->datapoint);
			}  else {
				$pnttot=0;
			}
			$sertot=0;$grptot=0;$serrels=[];$grprels=[];

			// dataseries
			if(!empty($this->dataseries)) {
				// add dataseries
				$points=[];
				foreach($this->dataseries as $seridx=>$series) {
					$sertot++;$serrels[$seridx]=[];
					if(!isset($series['points'])) {
						// generate an index of points
						if(isset($series['ids'])) {
							$points=array_keys($series['ids']);
						} else {
							$ckeys=$dkeys=$vkeys=$skeys=[];
							if(!empty($group['cons'])) {
								foreach($group['cons'] as $prop=>$pnts) {
									$ckeys=array_merge($ckeys,array_keys($pnts));
								}
							}
							if(!empty($group['data'])) {
								foreach($group['data'] as $prop=>$pnts) {
									$dkeys = array_merge($dkeys, array_keys($pnts));
								}
							}
							if(!empty($group['drvs'])) {
								foreach($group['drvs'] as $prop=>$pnts) {
									$vkeys = array_merge($vkeys, array_keys($pnts));
								}
							}
							if(!empty($group['sups'])) {
								foreach($group['sups'] as $prop=>$pnts) {
									$skeys = array_merge($skeys, array_keys($pnts));
								}
							}
							$points=array_merge($ckeys,$dkeys,$vkeys,$skeys);
						}
					}
					$ser["@id"]='dataseries/'.$seridx.'/';
					$ser["@type"]="sdo:dataseries";
					$ser['title']=$series['title'];
					if(!empty($series['system'])) {
						$ser['system']=$series['system'];
					} else {
						$ser['system']="no system?";
					}
					if(!empty($series['anns']['column'])) {
						$ser['annotations']=$series['anns']['column'];
					}
					$ser['datapoints']=[];
					foreach($points as $p) {
						$pnttot++;
						// array of datapoints
						$dpnt=[];
						$dpntid='datapoint/'.$pnttot.'/';
						if(isset($series['ids'][$p])) {
							$dpnt['id']=$series['ids'][$p];
						}
						if(isset($condrels['condition'][$seridx])) {
							$dpnt['conditions']=[];
							foreach($condrels['condition'][$seridx] as $cond) {
								$dpnt['conditions'][]=$cond[$p];
							}
						}
						$dpnt['values']=[];
						if(!empty($series['data'])) {
							foreach($series['data'] as $prop=>$pnts) {
								// add data columns
								if(!empty($pnts[$p])) {
									$val=[];$dtype='exptdata';
									$val['type']=$dtype;
									if(!empty($pnts[$p]['property'])) {
										$val['property']=$pnts[$p]['property'];
									} else {
										$pmeta=$ppty->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
										$val['property']=$pmeta['Property']['name'];
									}
									if(!empty($olinks[$dtype][$prop])) {
										$val['propertyref']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['propertyref'])) {
										$val['propertyref']=$pnts[$p]['propertyref'];
									}
									if(!empty($val['propertyref'])) {
										$graph['ids'][]=$val['propertyref'];
									}
									if(!empty($series['anns']['rows'][$prop][$p])) {
										$val['annotation']=$series['anns']['rows'][$prop][$p];
									}
									if(!empty($pnts[$p]['equality'])) {
										$val['equality']=$pnts[$p]['equality'];
									}
									if(!empty($pnts[$p]['value'])||$pnts[$p]['value']==0) { // zero is not false here
										$val['value'] = $pnts[$p]['value'];
									}
									$val['text']=$pnts[$p]['text'];
									if(!empty($pnts[$p]['sf'])) {
										$val['sf'] = $pnts[$p]['sf'];
									}
									if(!empty($pnts[$p]['unit'])) {
										$val['unit']=$pnts[$p]['unit'];
									}
									if(!empty($pnts[$p]['error'])) {
										$val['error']=$pnts[$p]['error'];
									}
									if(!empty($pnts[$p]['note'])) {
										$val['note']=$pnts[$p]['note'];
									}
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($series['sups'])) {
							foreach($series['sups'] as $prop=>$pnts) {
								// add supp columns
								if(!empty($pnts[$p])) {
									$val=[];$dtype='suppdata';
									$val['type']=$dtype;
									if(!empty($pnts[$p]['property'])) {
										$val['property']=$pnts[$p]['property'];
									} else {
										$pmeta=$ppty->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
										$val['property']=$pmeta['Property']['name'];
									}
									if(!empty($olinks[$dtype][$prop])) {
										$val['propertyref']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['propertyref'])) {
										$val['propertyref']=$pnts[$p]['propertyref'];
									}
									if(!empty($val['propertyref'])) {
										$graph['ids'][]=$val['propertyref'];
									}
									if(!empty($series['anns']['rows'][$prop][$p])) {
										$val['annotation']=$series['anns']['rows'][$prop][$p];
									}
									if(!empty($pnts[$p]['equality'])) {
										$val['equality']=$pnts[$p]['equality'];
									}
									if(!empty($pnts[$p]['value'])||$pnts[$p]['value']==0) { // zero is not false here
										$val['value'] = $pnts[$p]['value'];
									}
									$val['text']=$pnts[$p]['text'];
									if(!empty($pnts[$p]['max'])) {
										$val['max']=$pnts[$p]['max'];
									}
									if(!empty($pnts[$p]['sf'])) {
										$val['sf'] = $pnts[$p]['sf'];
									}
									if(!empty($pnts[$p]['unit'])) {
										$val['unit']=$pnts[$p]['unit'];
									}
									if(!empty($pnts[$p]['error'])) {
										$val['error']=$pnts[$p]['error'];
									}
									if(!empty($pnts[$p]['note'])) {
										$val['note']=$pnts[$p]['note'];
									}
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($series['drvs'])) {
							foreach($series['drvs'] as $prop=>$pnts) {
								// add supp columns
								if(!empty($pnts[$p])) {
									$val=[];$dtype='deriveddata';
									$val['type']=$dtype;
									if(!empty($pnts[$p]['property'])) {
										$val['property']=$pnts[$p]['property'];
									} else {
										$pmeta=$ppty->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
										$val['property']=$pmeta['Property']['name'];
									}
									if(!empty($olinks[$dtype][$prop])) {
										$val['propertyref']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['propertyref'])) {
										$val['propertyref']=$pnts[$p]['propertyref'];
									}
									if(!empty($val['propertyref'])) {
										$graph['ids'][]=$val['propertyref'];
									}
									if(!empty($series['anns']['rows'][$prop][$p])) {
										$val['annotation']=$series['anns']['rows'][$prop][$p];
									}
									if(!empty($pnts[$p]['equality'])) {
										$val['equality']=$pnts[$p]['equality'];
									}
									if(!empty($pnts[$p]['value'])||$pnts[$p]['value']==0) { // zero is not false here
										$val['value'] = $pnts[$p]['value'];
									}
									$val['text']=$pnts[$p]['text'];
									if(!empty($pnts[$p]['max'])) {
										$val['max']=$pnts[$p]['max'];
									}
									if(!empty($pnts[$p]['sf'])) {
										$val['sf'] = $pnts[$p]['sf'];
									}
									if(!empty($pnts[$p]['unit'])) {
										$val['unit']=$pnts[$p]['unit'];
									}
									if(!empty($pnts[$p]['error'])) {
										$val['error']=$pnts[$p]['error'];
									}
									if(!empty($pnts[$p]['note'])) {
										$val['note']=$pnts[$p]['note'];
									}
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($series['anns']['general'])) {
							foreach ($series['anns']['general'] as $field => $pnts) {
								// add anns columns
								$val=[];
								$val['type']='annotation';
								$val['text']=$pnts[$p];
								if(!empty($pnts[$p]['note'])) {
									$val['note']=$pnts[$p]['note'];
								}
								$dpnt['values'][]=$val;
							}
						}
						$this->setdatapoint($dpnt);
						$ser['datapoints'][]=$dpntid;
					}
					$set['dataseries'][]=$ser;
				}
			}

			// datagroups
			if(!empty($this->datagroup)) {
				// add datagroup
				foreach($this->datagroup as $grpidx=>$group) {
					// debug($group);
					$grptot++;$grprels[$grpidx]=[];$points=[];
					if(!isset($group['points'])) {
						// generate an index of points
						if(isset($group['ids'])) {
							$points=array_keys($group['ids']);
						} else {
							$ckeys=$dkeys=$vkeys=$skeys=[];
							if(!empty($group['cons'])) {
								foreach($group['cons'] as $prop=>$pnts) {
									$ckeys=array_merge($ckeys,array_keys($pnts));
								}
							}
							if(!empty($group['data'])) {
								foreach($group['data'] as $prop=>$pnts) {
									$dkeys = array_merge($dkeys, array_keys($pnts));
								}
							}
							if(!empty($group['drvs'])) {
								foreach($group['drvs'] as $prop=>$pnts) {
									$vkeys = array_merge($vkeys, array_keys($pnts));
								}
							}
							if(!empty($group['sups'])) {
								foreach($group['sups'] as $prop=>$pnts) {
									$skeys = array_merge($skeys, array_keys($pnts));
								}
							}
							$points=array_merge($ckeys,$dkeys,$vkeys,$skeys);
						}
					} else {
						$points=$group['points'];
					}
					$grp["@id"]='datagroup/'.$grpidx.'/';
					$grp["@type"]="sdo:datagroup";
					$grp['title']=$group['title'];
					if(!empty($group['anns']['column'])) {
						$grp['annotations']=$group['anns']['column'];
					}
					$grp['datapoints']=[];
					//debug($points);//exit;
					foreach($points as $p) {
						$pnttot++;
						// array of datapoints
						$dpnt=[];
						$dpntid='datapoint/'.$pnttot.'/';
						if(isset($series['ids'][$p])) {
							$dpnt['id']=$group['ids'][$p];
						}
						if(isset($condrels['condition'][$grpidx])) {
							$dpnt['conditions']=[];
							//debug($p);debug($condrels);//exit;

							foreach($condrels['condition'][$grpidx] as $cond) {
								$dpnt['conditions'][]=$cond[$p];
							}
						}
						$dpnt['values']=[];
						if(!empty($group['data'])) {
							foreach($group['data'] as $prop=>$pnts) {
								// add data columns
								if(!empty($pnts[$p])) {
									$val=[];$dtype='exptdata';
									$val['type']=$dtype;
									if(!empty($pnts[$p]['property'])) {
										$val['property']=$pnts[$p]['property'];
									} else {
										$pmeta=$ppty->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
										$val['property']=$pmeta['Property']['name'];
									}
									if(!empty($olinks[$dtype][$prop])) {
										$val['propertyref']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['propertyref'])) {
										$val['propertyref']=$pnts[$p]['propertyref'];
									}
									if(!empty($series['anns']['rows'][$prop][$p])) {
										$val['annotation']=$series['anns']['rows'][$prop][$p];
									}
									if(!empty($pnts[$p]['equality'])) {
										$val['equality']=$pnts[$p]['equality'];
									}
									if(!empty($pnts[$p]['value'])||$pnts[$p]['value']==0) { // zero is not false here
										$val['value'] = $pnts[$p]['value'];
									}
									$val['text']=$pnts[$p]['text'];
									if(!empty($pnts[$p]['sf'])) {
										$val['sf'] = $pnts[$p]['sf'];
									}
									if(!empty($pnts[$p]['unit'])) {
										$val['unit']=$pnts[$p]['unit'];
									}
									if(!empty($pnts[$p]['unitref'])) {
										$val['unitref']=$pnts[$p]['unitref'];
									}
									if(!empty($pnts[$p]['error'])) {
										$val['error']=$pnts[$p]['error'];
									}
									if(!empty($pnts[$p]['note'])) {
										$val['note']=$pnts[$p]['note'];
									}
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($group['sups'])) {
							foreach($group['sups'] as $prop=>$pnts) {
								// add supp columns
								if(!empty($pnts[$p])) {
									$val=[];$dtype='suppdata';
									$val['type']=$dtype;
									if(!empty($pnts[$p]['property'])) {
										$val['property']=$pnts[$p]['property'];
									} else {
										$pmeta=$ppty->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
										$val['property']=$pmeta['Property']['name'];
									}
									if(!empty($olinks[$dtype][$prop])) {
										$val['propertyref']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['propertyref'])) {
										$val['propertyref']=$pnts[$p]['propertyref'];
									}
									if(!empty($val['propertyref'])) {
										$graph['ids'][]=$val['propertyref'];
									}
									if(!empty($series['anns']['rows'][$prop][$p])) {
										$val['annotation']=$series['anns']['rows'][$prop][$p];
									}
									if(!empty($pnts[$p]['equality'])) {
										$val['equality']=$pnts[$p]['equality'];
									}
									if(!empty($pnts[$p]['value'])||$pnts[$p]['value']==0) { // zero is not false here
										$val['value'] = $pnts[$p]['value'];
									}
									$val['text']=$pnts[$p]['text'];
									if(!empty($pnts[$p]['max'])) {
										$val['max']=$pnts[$p]['max'];
									}
									if(!empty($pnts[$p]['sf'])) {
										$val['sf'] = $pnts[$p]['sf'];
									}
									if(!empty($pnts[$p]['unit'])) {
										$val['unit']=$pnts[$p]['unit'];
									}
									if(!empty($pnts[$p]['error'])) {
										$val['error']=$pnts[$p]['error'];
									}
									if(!empty($pnts[$p]['note'])) {
										$val['note']=$pnts[$p]['note'];
									}
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($group['drvs'])) {
							//debug($group['drvs']);
							foreach($group['drvs'] as $prop=>$pnts) {
								// add derived data columns
								if(!empty($pnts[$p])) {
									$val=[];$dtype='deriveddata';
									$val['type']=$dtype;
									if(!empty($pnts[$p]['property'])) {
										$val['property']=$pnts[$p]['property'];
									} else {
										$pmeta=$ppty->find('first',['conditions'=>['datafield like'=>"%'".$prop."'%"],'contain'=>['Quantity'=>'Unit'],'recursive'=>-1]);
										$val['property']=$pmeta['Property']['name'];
									}
									if(!empty($olinks[$dtype][$prop])) {
										$val['propertyref']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['propertyref'])) {
										$val['propertyref']=$pnts[$p]['propertyref'];
									}
									if(!empty($val['propertyref'])) {
										$graph['ids'][]=$val['propertyref'];
									}
									if(!empty($series['anns']['rows'][$prop][$p])) {
										$val['annotation']=$series['anns']['rows'][$prop][$p];
									}
									if(!empty($pnts[$p]['equality'])) {
										$val['equality']=$pnts[$p]['equality'];
									}
									if(!empty($pnts[$p]['value'])||$pnts[$p]['value']==0) { // zero is not false here
										$val['value'] = $pnts[$p]['value'];
									}
									$val['text']=$pnts[$p]['text'];
									if(!empty($pnts[$p]['max'])) {
										$val['max']=$pnts[$p]['max'];
									}
									if(!empty($pnts[$p]['sf'])) {
										$val['sf'] = $pnts[$p]['sf'];
									}
									if(!empty($pnts[$p]['unit'])) {
										$val['unit']=$pnts[$p]['unit'];
									}
									if(!empty($pnts[$p]['error'])) {
										$val['error']=$pnts[$p]['error'];
									}
									if(!empty($pnts[$p]['note'])) {
										$val['note']=$pnts[$p]['note'];
									}
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($group['anns']['general'])) {
							foreach($group['anns']['general'] as $field => $pnts) {
								// add anns columns
								$val=[];
								$val['type']='annotation';
								$val['text']=$pnts[$p];
								if(!empty($pnts[$p]['note'])) {
									$val['note']=$pnts[$p]['note'];
								}
								$dpnt['values'][]=$val;
							}
						}
						$this->setdatapoint($dpnt);
						$grp['datapoints'][]=$dpntid;
					}
					$set['datagroup'][]=$grp;
				}
			}

			// datapoints
			$sysrows=$this->sysrows;
			if(!empty($this->datapoint)) {
				// add datapoints
				foreach($this->datapoint as $pidx=>$pnt) {
					$dpnt=[];
					$dpnt["@id"]='datapoint/'.$pidx.'/';
					$dpnt["@type"]="sdo:datapoint";  // TODO add datatype: exptdata etc...
					if(isset($pnt['id'])) {
						$dpnt['id']=$pnt['id'];
					}
					if(isset($pnt['conditions'])) {
						$dpnt['conditions']=$pnt['conditions'];
					}
					if(isset($sysrows)) {
						$dpnt['system']=$sysrows[$pidx];
					}
					if(count($pnt['values'])==1) {
						$pnt=$pnt['values'][0];
						if(isset($pnt['quantity'])) {
							$dpnt['quantity']=$pnt['quantity'];
						}
						if(isset($pnt['property'])) {
							$dpnt['property']=$pnt['property'];
						}
						if(isset($pnt['propertyref'])) {
							$dpnt['propertyref']=$pnt['propertyref'];
							$graph['ids'][]=$pnt['propertyref'];
						}
						if(isset($pnt['unit'])) {
							$dpnt['unit']=$pnt['unit'];
						}
						if(isset($pnt['unitref'])) {
							$dpnt['unitref']=$pnt['unitref'];
							$graph['ids'][]=$pnt['unitref'];
						}
						if(isset($pnt['annotation'])) {
							$dpnt['annotation']=$pnt['annotation'];
						}
						if(isset($pnt['value'])) {
							$val=[];
							$val["@id"]=$dpnt["@id"].'value/';
							$val["@type"]="sdo:numericValue";
							if(is_float($pnt['value'])) {
								$val['datatype']='float';
							} else {
								$val['datatype']='integer';
							}
							if(isset($pnt['equality'])) {
								$val['equality']=$pnt['equality'];
							}
							if(!empty($pnt['max'])) {
								$val['min']=$pnt['text']; // using the string as it is safer in JSON
								$val['max']=$pnt['max'];
							} else {
								$val['number']=$pnt['text']; // using the string as it is safer in JSON
							}
							if(!empty($pnt['sf'])) {
								$val['sigfigs']=$pnt['sf'];
							}
							if(!empty($pnt['error'])) {
								$val['error']=$pnt['error'];
							}
							if(!empty($pnt['note'])) {
								$val['note']=$pnt['note'];
							}
							$dpnt['numericvalue']=$val;
						} elseif(isset($pnt['text'])) {
							$val=[];
							$val["@id"]=$dpnt["@id"].'value/';
							$val["@type"]="sdo:textValue";
							$val['text']=$pnt['text'];
							$data['textstring']=$val;
						}
					} else {
						$dpnt['data']=[];
						foreach($pnt['values'] as $didx=>$datum) {
							$data=[];
							$data["@id"]=$dpnt["@id"].'datum/'.($didx+1).'/';
							$data["@type"]="sdo:".$datum['type'];
							if(isset($datum['quantity'])) {
								$data['quantity']=$datum['quantity'];
							}
							if(isset($datum['property'])) {
								$data['property']=$datum['property'];
							}
							if(isset($datum['propertyref'])) {
								$data['propertyref']=$datum['propertyref'];
								$graph['ids'][]=$datum['propertyref'];
							}
							if(isset($datum['unit'])) {
								$data['unit']=$datum['unit'];
							}
							if(isset($datum['unitref'])) {
								$data['unitref']=$datum['unitref'];
								$graph['ids'][]=$datum['unitref'];
							}
							if(isset($datum['annotation'])) {
								$data['annotation']=$datum['annotation'];
							}
							if(isset($datum['value'])) {
								$val=[];
								$val["@id"]=$data["@id"].'value/';
								$val["@type"]="sdo:numericValue";
								if(is_float($datum['value'])) {
									$val['datatype']='float';
								} else {
									$val['datatype']='integer';
								}
								if(isset($datum['equality'])) {
									$val['equality']=$datum['equality'];
								}
								if(!empty($datum['max'])) {
									$val['min']=$datum['text'];
									$val['max']=$datum['max'];
								} else {
									$val['number']=$datum['text'];
								}
								if(!empty($datum['sf'])) {
									$val['sigfigs']=$datum['sf'];
								}
								if(!empty($datum['error'])) {
									$val['error']=$datum['error'];
								}
								if(!empty($datum['note'])) {
									$val['note']=$datum['note'];
								}
								$data['numericvalue']=$val;
							} elseif(isset($datum['text'])) {
								$val=[];
								$val["@id"]=$data["@id"].'value/';
								$val["@type"]="sdo:textValue";
								$val['text']=$datum['text'];
								if(!empty($datum['note'])) {
									$val['note']=$datum['note'];
								}
								$data['textstring']=$val;
							}
							$dpnt['data'][]=$data;
						}
					}
					$set['datapoint'][]=$dpnt;
				}
			}
		}
		$sci['dataset']=$set;

		$graph['scidata']=$sci;

		// sources
		if(!empty($this->sources)) {
			$srcs=[];
			if(!empty($this->sources[0])) {
				$srcs=$this->sources;
			} else {
				$srcs[]=$this->sources;
			}
			foreach($srcs as $idx=>$src) {
				$source=[];
				$source["@id"]='source/'.($idx+1).'/';
				$source["@type"]="sdo:source";
				$graph['toc'][]=$source['@type'];
				if(!empty($src['journal']))		{ $source["journal"]=$src['journal']; }
				if(!empty($src['citation']))	{ $source["citation"]=$src['citation']; }
				if(!empty($src['doi'])) 		{ $source["doi"]=$src['doi']; }
				if(!empty($src['url'])) 		{ $source["url"]=$src['url']; }
				if(!empty($src['type'])) 		{ $source["type"]=$src['type']; }
				$graph["sources"][]=$source;
			}
		} else {
			unset($output["sources"]);
		}

		// rights
		if(!empty($this->rights)) {
			$rs=[];
			if(!empty($this->rights[0])) {
				$rs=$this->rights;
			} else {
				$rs[0]=$this->rights;
			}
			foreach($rs as $idx=>$r) {
				$right=[];
				$right["@id"]='rights/'.($idx+1).'/';
				$right["@type"]="sdo:rights";
				$graph['toc'][]=$right['@type'];
				$right=array_merge($right,$r);
				$graph["rights"][]=$right;
			}
		} else {
			unset($output["rights"]);
		}

		// dedup and order toc
		$graph['toc']=array_unique($graph['toc']);
		sort($graph['toc']);
		$this->toc=$graph['toc'];

		// dedup and order ids
		$graph['ids']=array_unique($graph['ids']);
		sort($graph['ids']);
		$this->ids=$graph['ids'];

		// update private vars
		$this->rels=$rels;

		// add the graph data
		$output["@graph"]=$graph;

		// remove empty array elements
		foreach($output as $key=>$part) {
			if(empty($output[$key])) {
				unset($output[$key]);
			}
			if($key=="@graph") {
				foreach($output["@graph"] as $key2=>$part2) {
					if(empty($output["@graph"][$key2])) {
						unset($output["@graph"][$key2]);
					}
				}
			}
		}
		return $output;
	}

	/**
	 * create output and return as json
	 * @return string
	 */
	public function asjsonld()
	{
		$output=$this->asarray();
		return json_encode($output,JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
	}

	/**
	 * export data from class variables to set format
	 */
	public function rawout()
	{
		$this->asarray();
		$output=[];
		$output['context']=$this->contexts;
		$output['path']=$this->path;
		$output['nspaces']=$this->nspaces;
		$output['id']=$this->id;
		$output['uid']=$this->uid;
		$output['base']=$this->base;
		$output['context']=$this->contexts;
		$output['meta']=$this->meta;
		$output['authors']=$this->authors;
		$output['related']=$this->related;
		$output['keywords']=$this->keywords;
		$output['startdate']=$this->startdate;
		$output['permalink']=$this->permalink;
		$output['toc']=$this->toc;
		$output['ids']=$this->ids;
		$output['report']=$this->report;
		$output['discipline']=$this->discipline;
		$output['subdiscipline']=$this->subdiscipline;
		$output['facets']=$this->facets;
		$output['aspects']=$this->aspects;
		$output['data']=$this->data;
		$output['dataseries']=$this->dataseries;
		$output['datagroup']=$this->datagroup;
		$output['datapoint']=$this->datapoint;
		$output['cpds']=$this->cpds;
		$output['syss']=$this->syss;
		$output['chms']=$this->chms;
		$output['sets']=$this->sets;
		$output['sers']=$this->sers;
		$output['pnts']=$this->pnts;
		$output['cnds']=$this->cnds;
		$output['sttgs']=$this->sttgs;
		$output['sources']=$this->sources;
		$output['rights']=$this->rights;
		$output['errors']=$this->errors;
		$output['ontlinks']=$this->ontlinks;
		$output['intlinks']=$this->intlinks;
		$output['sysrows']=$this->sysrows;
		return $output;
	}

	/**
	 * private function to create data structure for conditions, data, and supplemental data
	 * @param array $facet
	 * @param string $type
	 * @param int $idx
	 * @param array $condrels
	 * @param array $graph
	 * @return array
	 */
	private function makedata($facet,$type,$idx,&$condrels,&$graph) {
		//debug($facet);exit;
		$prop=$facet['property'];
		$unit=$facet['unit'];
		$vals=$facet['value'];
		$errs=null;
		if(isset($facet['errors'])) {
			$errs=$facet['errors'];
		}
		$output=[];
		if($type=='condition') {
			$output['property']=$prop;
			if(isset($facet['propertyref'])) {
				$output['propertyref']=$facet['propertyref'];
				$graph['ids'][]=$facet['propertyref'];
			}
			if(isset($facet['propid'])) {
				$output['propertyref']=$facet['propid'];
				$graph['ids'][]=$facet['propid'];
			}
			$output['unit']=$unit;
			if(!empty($facet['unitref'])) {
				$output['unitref']=$facet['unitref'];
				$graph['ids'][]=$facet['unitref'];
			}
			if(is_array($vals)) {
				$output['valuearray']=[];$vidx=1;
				foreach($vals as $val) {
					$serrows=$val['rows'];
					if(isset($val['meta'])) {
						$val=$val['meta'];
					} elseif(isset($val['value'])) {
						$val=$this->exponentialGen($val['value']);
					}
					$value=[];
					$value['@id']='condition/'.$idx.'/value/'.$vidx.'/';
					$value['@type']='sdo:numericValue';
					if(is_float($val['value'])) {
						$value['datatype']='float';
					} elseif(is_int($val['value'])) {
						$value['datatype']='integer';
					}
					$value['sigfigs']=$val['sf'];
					$value['number']=$val['scinot'];
					if(!empty($val['unit'])) {
						$value['unit']=$val['unit'];
					}
					if(!empty($errs)) {
						$value['error']=$errs['val'];
						if(!is_null($errs['note'])) { $value['errnote']=$errs['note']; }
					} elseif($val['error']!='') {
						$value['error']=$val['error'];
					}
					$output['valuearray'][]=$value;
					foreach($serrows as $serrow) {
						list($ser,$row)=explode(':',$serrow);
						$condrels[$type][$ser][$prop][$row]=$value['@id'];
					}
					//debug($condrels);
					$vidx++;
				}
			} else {
				$output['value']=[];
				$value=[];$val=$vals;
				$value['@id']='condition/'.$idx.'/value/';
				$value['@type']='sdo:numericValue';
				if(is_float($vals)) {
					$value['datatype']='float';
				} elseif(is_int($vals)) {
					$value['datatype']='integer';
				}
				$value['number']=$val;
				if(!is_null($unit)) {
					$value['unit']=$unit;
				}
				if(!empty($errs)) {
					$value['error']=$errs['val'];
					if(!is_null($errs['note'])) { $value['note']=$errs['note']; }
				}
				$output['value']=$value;
				$condrels[$type][$idx][$prop]=[$value['@id']=>$val];
			}
		}
		return $output;
	}

	/**
	 * Generates a exponential number removing any zeros at the end not needed
	 * @param $string
	 * @return array
	 */
	private function exponentialGen($string) {
		$return=[];
		$return['text']=$string;
		$return['value']=floatval($string);
		if($string==0) {
			$return+=['dp'=>0,'scinot'=>'0e+0','exponent'=>0,'significand'=>0,'error'=>null,'sf'=>0];
		} elseif(stristr($string,'E')) {
			list($man,$exp)=explode('E',$string);
			if($man>0){
				$sf=strlen($man)-1;
			} else {
				$sf=strlen($man)-2;
			}
			$return['scinot']=$string;
			$return['error']=pow(10,$exp-$sf+1);
			$return['exponent']=$exp;
			$return['significand']=$man;
			$return['dp']=$sf;
		} else {
			$string=str_replace([",","+"],"",$string);
			$num=explode(".",$string);
			$neg=false;
			if(stristr($num[0],'-')) {
				$neg=true;
			}
			// If there is something after the decimal
			if(isset($num[1])){
				$return['dp']=strlen($num[1]);
				if($num[0]!=""&&$num[0]!=0) {
					// All digits count (-1 for period)
					if($neg) {
						// substract 1 for the minus sign and 1 for decimal point
						$return['sf']=strlen($string)-2;
						$return['exponent']=strlen($num[0])-2;
					} else {
						$return['sf']=strlen($string)-1;
						$return['exponent']=strlen($num[0])-1;
					}
					// Exponent is based on digit before the decimal -1
				} else {
					// Remove any leading zeroes after decimal and count string length
					$return['sf']=strlen(ltrim($num[1],'0'));
					// Count leading zeros
					preg_match('/^(0*)[1234567890]+$/',$num[1],$match);
					$return['exponent']=-1*(strlen($match[1]) + 1);
				}
				$return['scinot']=sprintf("%." .($return['sf']-1). "e", $string);
				$s=explode("e",$return['scinot']);
				$return['significand']=$s[0];
				$return['error']=pow(10,$return['exponent']-$return['sf']+1);
			} else {
				$return['dp']=0;
				$return['scinot']=sprintf("%." .(strlen($string)-1). "e", $string);
				$s=explode("e",$return['scinot']);
				$return['significand']=$s[0];
				$return['exponent'] = $s[1];
				$z=explode(".",$return['significand']);
				$return['sf']=strlen($return['significand'])-1;
				// Check for negative
				if(isset($z[1])) {
					$return['error']=pow(10,strlen($z[1])-$s[1]-$neg); // # SF after decimal - exponent
				} else {
					$return['error']=pow(10,0-$s[1]); // # SF after decimal - exponent
				}
			}
		}
		return $return;
	}

}
