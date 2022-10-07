<?php

/**
 * Class Scidata
 * Scidata model (not related to a database table)
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Scidata extends AppModel
{

	public $useTable=false;

	public ?string $path=null;
	public array $contexts=["https://stuchalk.github.io/scidata/contexts/scidata.jsonld"];
	public array $nspaces=[
		'sdo'=>'https://stuchalk.github.io/scidata/ontology/scidata.owl#',
		'dc'=>'http://purl.org/dc/terms/',
		'qudt'=>'http://qudt.org/vocab/unit/',
		'xsd'=>'http://www.w3.org/2001/XMLSchema#',
		'ss'=>'https://semanticchemistry.github.io/semanticchemistry/ontology/cheminf.owl#'];
	public $id=null;
	public ?string $generatedat=null;
	public ?string $version=null;
	public ?string $graphid=null;
	public ?string $uid=null;
	public ?string $base=null;
	public ?array $meta=null;
	public ?array $creators=null;
	public ?array $related=null;
	public ?array $keywords=null;
	public ?string $starttime=null;
	public ?string $permalink=null;
	public ?array $toc=null;
	public ?array $ids=null;
	public ?array $report=null;
	public ?string $discipline=null;
	public ?string $subdiscipline=null;
	public ?array $facets=null;
	public ?array $aspects=null;
	public $data=null;
	public ?array $dataseries=null;
	public ?array $datagroup=null;
	public ?array $datapoint=null;
	private array $cpds=[];
	private array $syss=[];
	private array $chms=[];
	private array $sets=[];
	private array $sers=[];
	private array $pnts=[];
	private array $cnds=[];
	private array $rels=[];
	// private array $scnds=[];
	private array $sttgs=[];
	public array $sources=[];
	public array $rights=[];
	public array $errors=[];
	public array $output=[];
	public array $ontlinks=[];
	public array $intlinks=[];
	public array $sysrows=[];

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
		$graph['creators']=[];
		$graph['description']="";
		$graph['publisher']="";
		$graph['starttime']="";
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
	 * get path
	 * @return bool
	 */
	public function path(): bool {
		if(!is_null($this->path)) {
			return $this->path;
		} else {
			return false;
		}
	}

	/**
	 * get base URL
	 * @return bool
	 */
	public function base(): bool {
		if(!is_null($this->base)) {
			return $this->base;
		} else {
			return false;
		}
	}

	/**
	 * get general meta
	 * @return bool|array
	 */
	public function meta(): bool {
		if(!is_null($this->meta)) {
			return $this->meta;
		} else {
			return false;
		}
	}

	/**
	 * get facet data
	 * @return bool|array
	 */
	public function getfacets(): bool {
		if(!is_null($this->facets)) {
			return $this->facets;
		} else {
			return false;
		}
	}

	// Setters

	/**
	 * set the links to the context files
	 * @param $value
	 * @return bool
	 */
	public function setcontexts($value=null): bool {
		if(!is_null($value)) {
			$this->setter("contexts","array",$value,'merge');
		}
		return false;
	}

	/**
	 * set the namespaces
	 * @param $value
	 * @return bool
	 */
	public function setnspaces($value=null): bool {
		if(!is_null($value)) {
			$this->setter("nspaces","array",$value,'merge');
		}
		return false;
	}

	/**
	 * set the id
	 * @param $value
	 * @return bool
	 */
	public function setuid($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("uid","string",$value);
		}
	}

	/**
	 * set graph id
	 * @param $value
	 * @return bool
	 */
	public function setgraphid($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("graphid","string",$value);
		}
	}

	/**
	 * set generatedAt date
	 * @param $value
	 * @return bool
	 */
	public function setgenat($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("generatedat","string",$value);
		}
	}

	/**
	 * set version
	 * @param $value
	 * @return bool
	 */
	public function setversion($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("version","integer",$value);
		}
	}

	/**
	 * set the path
	 * @param $value
	 * @return bool
	 */
	public function setpath($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("path","string",$value);
		}
	}

	/**
	 * set the base
	 * @param $value
	 * @return bool
	 */
	public function setbase($value=null): bool {
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
	 * set the meta
	 * @param $value
	 * @return bool
	 */
	public function setmeta($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("meta","array",$value);
		}
	}

	/**
	 * set the uid
	 * @param $value
	 * @return bool
	 */
	public function setid($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("id","string",$value);
		}
	}

	/**
	 * set the title
	 * @param $value
	 * @return bool
	 */
	public function settitle($value=null): bool {
		if($value==null) {
			return false;
		} else {
			$meta=$this->meta;
			$meta['title']=$value;
			$this->setmeta($meta);
			return $this->setter("title","string",$value);
		}
	}

	/**
	 * set the publisher
	 * @param $value
	 * @return bool
	 */
	public function setpublisher($value=null): bool  {
		if($value==null) {
			return false;
		} else {
			$meta=$this->meta;
			$meta['publisher']=$value;
			$this->setmeta($meta);
			return $this->setter("publisher","string",$value);
		}
	}

	/**
	 * set the description
	 * @param $value
	 * @return bool
	 */
	public function setdescription($value=null): bool {
		if($value==null) {
			return false;
		} else {
			$meta=$this->meta;
			$meta['description']=$value;
			$this->setmeta($meta);
			return $this->setter("description","string",$value);
		}
	}

	/**
	 * set the related array
	 * @param $value
	 * @return bool
	 */
	public function setrelated($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("related","array",$value);
		}
	}

	/**
	 * set the keywords array
	 * @param $value
	 * @return bool
	 */
	public function setkeywords($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("keywords","array",$value);
		}
	}

	/**
	 * set the toc array
	 * @param $value
	 * @return bool
	 */
	public function settoc($value=null): bool {
		if($value==null) {
			return false;
		} else {
			$toc=$this->toc;
			$toc=$toc+$value;
			$toc=array_unique($toc);
			sort($toc);
			return $this->setter("toc","array",$value);
		}
	}

	/**
	 * set the report array
	 * @param $value
	 * @return bool
	 */
	public function setreport($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("report","array",$value);
		}
	}

	/**
	 * set creators
	 * @param null $value
	 * @return bool
	 */
	public function setcreators($value=null): bool {
		if($value==null) {
			return false;
		} else {
			$creators=[];
			if(!is_array($value)) {
				if(stristr($value,'{')) {
					$creators=json_decode($value,true);
				} elseif(stristr($value,', ')) {
					$creators=explode(", ",$value);
				} else {
					$creators[0]=$value;
				}
			} else {
				$creators=$value;
			}
			return $this->setter("creators","array",$creators);
		}
	}

	/**
	 * set the starttime
	 * @param $value
	 * @return bool
	 */
	public function setstarttime($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("starttime","string",$value);
		}
	}

	/**
	 * set the permalink
	 * @param $value
	 * @return bool
	 */
	public function setpermalink($value=null): bool {
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
	 * set the ids array (array of semantic identifiers)
	 * @param $value
	 * @return bool
	 */
	public function setids($value=null): bool {
		if($value==null) {
			return false;
		} else {
			$ids=$this->ids;
			$ids=$ids+$value;
			$ids=array_unique($ids);
			sort($ids);
			return $this->setter("ids","array",$ids);
		}
	}

	/**
	 * set the discipline
	 * @param $value
	 * @return bool
	 */
	public function setdiscipline($value=null): bool {
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
	 * set the subdiscipline
	 * @param $value
	 * @return bool
	 */
	public function setsubdiscipline($value=null): bool {
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
	 * set the aspects
	 * @param null $value
	 * @return bool
	 */
	public function setaspects($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("aspects","array",$value);
		}
	}

	/**
	 * set the facets
	 * @param null $value
	 * @return bool
	 */
	public function setfacets($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("facets","array",$value);
		}
	}

	/**
	 * set the sysrows (for a document with multiple systems, the rows of data that are for each system)
	 * @param null $value
	 * @return bool
	 */
	public function setsysrows($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("sysrows","array",$value);
		}
	}

	/**
	 * set the data
	 * @param null $value
	 * @return bool
	 */
	public function setdata($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("data","array",$value);
		}
	}

	/**
	 * set a dataseries
	 * @param null $value
	 * @return bool
	 */
	public function setdataseries($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("dataseries","array",$value);
		}
	}

	/**
	 * set a datagroup
	 * @param null $value
	 * @return bool
	 */
	public function setdatagroup($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("datagroup","array",$value);
		}
	}

	/**
	 * set a datapoint
	 * @param null $value
	 * @param string $mode (append|replace|clear)
	 * @return bool
	 */
	public function setdatapoint($value=null,string $mode='append'): bool {
		if($value==null||!is_array($value)) {
			return false;
		} else {
			return $this->setter("datapoint","array",$value,$mode);
		}
	}

	/**
	 * set the sources
	 * @param $value
	 * @return bool
	 */
	public function setsources($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("sources","array",$value);
		}
	}

	/**
	 * set the rights
	 * @param array|null $value
	 * @return bool
	 */
	public function setrights(array $value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("rights","array",$value);
		}
	}

	/**
	 * set the ontology links
	 * @param $value
	 * @return bool
	 */
	public function setontlinks($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("ontlinks","array",$value);
		}
	}

	/**
	 * set the internal links
	 * @param $value
	 * @return bool
	 */
	public function setintlinks($value=null): bool {
		if($value==null) {
			return false;
		} else {
			return $this->setter("intlinks","array",$value);
		}
	}

	/**
	 * generic setter method
	 * @param string $prop
	 * @param string $type
	 * @param string|array $value
	 * @param string $mode (append|replace|clear)
	 * @return bool
	 */
	private function setter(string $prop,string $type, $value,string $mode='replace'): bool {
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
	 * @param bool $flat
	 * @return mixed
	 */
	public function asarray(bool $flat=false): array {
		$Set=ClassRegistry::init('Dataset');
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

		// creators
		if(!is_null($this->creators)) {
			foreach ($this->creators as $idx=>$au) {
				if(is_string($au)) {
					$graph['creators'][]=['@id'=>'creator/'.($idx+1).'/','@type'=>'dc:creator','name'=>$au];
				} else {
					// remove any metadata elements not defined in SciData
					$allowed=['name','firstname','lastname','address','organization','email','orcid','role'];
					foreach($au as $label=>$value) {
						if (!in_array($label,$allowed)) { unset($au[$label]); }
					}
					$au = ['@id'=>'creator/'.($idx+1).'/','@type'=>'dc:creator'] + $au;
					$graph['creators'][]=$au;
				}
			}
		} else {
			unset($output["creators"]);
		}

		// starttime
		if(!is_null($this->starttime)) {
			$date=$this->starttime;
			// work out format and convert to UTC
			if(!stristr($date,"T")) {
				if(is_numeric($date)) {
					$date=date(DATE_ATOM,$date);
				} elseif(stristr($date,",")) {
					$date=date(DATE_ATOM,strtotime($date));
				}
			}
			$graph["starttime"]=$date;
		} else {
			unset($output["starttime"]);
		}

		// permalink
		if(!is_null($this->permalink)) {
			$graph["permalink"]=$this->permalink;
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
						//debug($facet);
						$facet=$this->makedata($facet,$type,$idx,$condrels,$graph, $flat);
						//debug($facet);exit;
					} else {
						foreach($facet as $label1=>$items1) {
							// check for ontology ids
							if(stristr($label1,'#')&&!is_array($items1)) {
								$graph['ids'][]=$items1;
							}
							if(substr($label1,-1)=='s') {
								$sublabel=substr($label1,0,-1);
							} else {
								$sublabel=$label1;
							}

							if(is_array($items1)) {
								foreach($items1 as $idx1=>$item1) {
									if(is_array($item1)) {
										foreach($item1 as $label2=>$items2) {
											if(substr($label2,-1)=='s') {
												$sublabel2=substr($label2,0,-1);
											} else {
												$sublabel2=$label2;
											}
											if(is_array($items2)) {
												foreach($items2 as $idx2=>$item2) {
													if(!isset($item2["@id"])) {
														$id2=$sublabel2.'/'.($idx2+1).'/';
													} else {
														$id2=$item2["@id"];
													}
													if(!isset($item2["@type"])) {
														$itype2 = 'sdo:' . $sublabel2;
													} else {
														$itype2=$item2["@type"];
													}
													//debug($item2);debug($idx2);debug($itype2);//exit;
													$item2=["@id"=>$id2,"@type"=>$itype2]+$item2;
													$items2[$idx2]=$item2;
												}
											}
											$item1[$label2]=$items2;
										}
										if(!isset($item1["@id"])) {
											$id1=$sublabel.'/'.($idx1+1).'/';
										} else {
											$id1=$item1["@id"];
										}
										if(!isset($item1["@type"])) {
											$itype1 = 'sdo:' . $sublabel;
										} else {
											$itype1=$item1["@type"];
										}
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
								foreach($group['cons'] as $pnts) {
									$ckeys=array_merge($ckeys,array_keys($pnts));
								}
							}
							if(!empty($group['data'])) {
								foreach($group['data'] as $pnts) {
									$dkeys = array_merge($dkeys, array_keys($pnts));
								}
							}
							if(!empty($group['drvs'])) {
								foreach($group['drvs'] as $pnts) {
									$vkeys = array_merge($vkeys, array_keys($pnts));
								}
							}
							if(!empty($group['sups'])) {
								foreach($group['sups'] as $pnts) {
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
										$val['quantity#']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['quantity#'])) {
										$val['quantity#']=$pnts[$p]['quantity#'];
									}
									if(!empty($val['quantity#'])) {
										$graph['ids'][]=$val['quantity#'];
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
										$val['quantity#']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['quantity#'])) {
										$val['quantity#']=$pnts[$p]['quantity#'];
									}
									if(!empty($val['quantity#'])) {
										$graph['ids'][]=$val['quantity#'];
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
										$val['quantity#']=$olinks[$dtype][$prop];
									}
									if(!empty($pnts[$p]['quantity#'])) {
										$val['quantity#']=$pnts[$p]['quantity#'];
									}
									if(!empty($val['quantity#'])) {
										$graph['ids'][]=$val['quantity#'];
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
					$grptot++;$grprels[$grpidx]=[];
					if(!isset($group['points'])) {
						// generate an index of points
						if(isset($group['ids'])) {
							$points=array_keys($group['ids']);
						} else {
							$ckeys=$dkeys=$vkeys=$skeys=[];
							if(!empty($group['cons'])) {
								foreach($group['cons'] as $pnts) {
									$ckeys=array_merge($ckeys,array_keys($pnts));
								}
							}
							if(!empty($group['data'])) {
								foreach($group['data'] as $pnts) {
									$dkeys = array_merge($dkeys, array_keys($pnts));
								}
							}
							if(!empty($group['drvs'])) {
								foreach($group['drvs'] as $pnts) {
									$vkeys = array_merge($vkeys, array_keys($pnts));
								}
							}
							if(!empty($group['sups'])) {
								foreach($group['sups'] as $pnts) {
									$skeys = array_merge($skeys, array_keys($pnts));
								}
							}
							$points=array_merge($ckeys,$dkeys,$vkeys,$skeys);
							$points=array_unique($points);
						}
					} else {
						$points=$group['points'];
					}
					$grp["@id"]='datagroup/'.$grpidx.'/';
					$grp["@type"]="sdo:datagroup";
					$grp['title']=$group['title'];
					# the next two lines replace the generic 'system' (i.e. chemicalsystem)
					# as "system" can be a mixture or pure compound (chemical)
					if(!empty($group['chemical'])) { $grp['chemical']=$group['chemical']; }
					if(!empty($group['mixture'])) { $grp['mixture']=$group['mixture']; }
					$grp['datapoints']=[];
					//debug($points);exit;
					foreach($points as $p) {
						//debug($p);
						// array of datapoints
						$dpnt=[];$pnttot++;
						$dpntid='datapoint/'.$pnttot.'/';
						if(isset($series['ids'][$p])) {
							$dpnt['id']=$group['ids'][$p];
						}
						if(isset($condrels['condition'][$grpidx])) {
							$dpnt['conditions']=[];
							//debug($p);debug($condrels);debug($grpidx);exit;

							foreach($condrels['condition'][$grpidx] as $cond) {
								if(isset($cond[$p])) {
									$dpnt['conditions'][]=$cond[$p];
								}
								//debug($cond);debug($dpnt['conditions']);
							}
						}
						$dpnt['values']=[];
						if(!empty($group['data'])) {
							foreach($group['data'] as $prop=>$pnts) {
								// add data columns
								if(!empty($pnts[$p])) {
									// passthough the value of the data as $val['number']
									// expGen done datapoints section
									$val=$this->passmeta($pnts[$p],'exptdata',$prop,$olinks,$graph);
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($group['sups'])) {
							foreach($group['sups'] as $prop=>$pnts) {
								// add supp columns
								if(!empty($pnts[$p])) {
									// passthough the value of the data as $val['number']
									// expGen done datapoints section
									$val=$this->passmeta($pnts[$p],'suppdata',$prop,$olinks,$graph);
									$dpnt['values'][]=$val;
								}
							}
						}
						if(!empty($group['drvs'])) {
							foreach($group['drvs'] as $prop=>$pnts) {
								// add derived data columns
								if(!empty($pnts[$p])) {
									// passthough the value of the data as $val['number']
									// expGen done datapoints section
									$val=$this->passmeta($pnts[$p],'derived',$prop,$olinks,$graph);
									$dpnt['values'][]=$val;
								}
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
					if(!empty($sysrows)) {
						$dpnt['system']=$sysrows[$pidx];
					}
					$dpnt['data']=[];
					foreach($pnt['values'] as $didx=>$datum) {
						$data=[];
						$data["@id"]=$dpnt["@id"].'datum/'.($didx+1).'/';$data["@type"]="sdo:".$datum['type'];
						if(isset($datum['quantitykind'])) { $data['quantitykind']=$datum['quantitykind']; }
						if(isset($datum['quantitykind#'])) {
							$data['quantitykind#']=$datum['quantitykind#'];
							$graph['ids'][]=$datum['quantitykind#'];
						}
						if(isset($datum['quantity'])) { $data['quantity']=$datum['quantity']; }
						if(isset($datum['quantity#'])) {
							$data['quantity#']=$datum['quantity#'];
							$graph['ids'][]=$datum['quantity#'];
						}
						if(isset($datum['phase'])) { $data['phase']=$datum['phase']; }
						if(isset($datum['related'])) { $data['related']=$datum['related'];}
						if(isset($datum['unit'])) { $data['unit']=$datum['unit']; }
						if(isset($datum['unit#'])) {
							$data['unit#']=$datum['unit#'];
							$graph['ids'][]=$datum['unit#'];
						}
						if(isset($datum['number'])) {
							$val=$Set->exponentialGen($datum['number']);$nval=[];
							if($flat) {
								$nval["@id"] = $data["@id"] . 'value/';
								$nval["@type"] = "sdo:numericValue";
							}
							if(isset($datum['equality'])) { $nval['equality']=$datum['equality']; }
							if(!empty($datum['max'])) {
								if($val['isint']) {
									$nval['datatype']='xsd:integer';
									$nval['min']=(int) $val['scinot'];
								} else {
									$nval['datatype']='xsd:float';
									$nval['min']=(float) $val['scinot'];
								}
								$max=$Set->exponentialGen($pnt['max']);
								if($val['isint']) {
									$nval['max']=(int) $max['scinot'];
								} else {
									$nval['max']=(float) $max['scinot'];
								}
							} else {
								if($val['isint']) {
									$nval['datatype']='xsd:integer';
									$nval['number']=(int) $val['scinot'];
								} else {
									$nval['datatype']='xsd:float';
									$nval['number']=(float) $val['scinot'];
								}
							}
							if(!empty($val['sf'])) { $nval['sigfigs']=$val['sf']; }
							if(!empty($datum['error'])) {
								$nval['error']=$datum['error'];
								if(!empty($datum['errortype'])) {
									$nval['errortype']=$datum['errortype'];
								} else {
									$nval['errortype']='unknown';
								}
								$nval['errornote']='from source';
							} elseif($val['error']!='') {
								$nval['error']=$val['error'];
								$nval['errortype']='absolute';
								$nval['errornote']='estimated from value';
							}
							if(!empty($datum['note'])) { $nval['note']=$datum['note']; }
							if($flat) {
								$data=$data+$nval;
							} else {
								$data['numericvalue']=$nval;
							}
						} elseif(isset($datum['text'])) {
							$tval=[];
							$tval["@id"]=$data["@id"].'value/';
							$tval["@type"]="sdo:textValue";
							$tval['text']=$datum['text'];
							if(!empty($datum['note'])) { $tval['note']=$datum['note']; }
							if($flat) {
								$data=$data+$tval;
							} else {
								$data['textstring']=$tval;
							}
						} else {
							echo "Missing/misassigned value!";
							debug($pnt);exit;
						}
						$dpnt['data'][]=$data;
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
				if(!empty($src['created'])) 	{ $source["created"]=$src['created']; }
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
			if($key!="@id") {
				if(empty($part)) {
					unset($output[$key]);
				}
			} else {
				$output["@id"]="";
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
	 * @param bool $flat
	 * @return string
	 */
	public function asjsonld(bool $flat=false): string {
		$output=$this->asarray($flat);
		return json_encode($output,JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION|JSON_NUMERIC_CHECK);
	}

	/**
	 * export data from class variables to set format
	 * @return array
	 */
	public function rawout(): array
	{
		$this->asarray();
		$output=[];
		$output['context']=$this->contexts;
		$output['path']=$this->path;
		$output['nspaces']=$this->nspaces;
		$output['id']=$this->id;
		$output['uid']=$this->uid;
		$output['base']=$this->base;
		$output['meta']=$this->meta;
		$output['creators']=$this->creators;
		$output['related']=$this->related;
		$output['keywords']=$this->keywords;
		$output['starttime']=$this->starttime;
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
	 * @param bool $flat
	 * @return array
	 */
	private function makedata(array $facet, string $type,int $idx,array &$condrels,array &$graph, bool $flat): array {
		$Set = ClassRegistry::init('Dataset');
		$quant='';
		if(isset($facet['quantity'])) {
			$quant=$facet['quantity'];
		} elseif(isset($facet['quantity#'])) {
			$quant=str_replace('#','',$facet['quantity#']);
		}
		if(!empty($facet['values'])) {
			// 'values' valuearray used in dataset scidataold function
			$vals=$facet['values'];
		} elseif(!empty($facet['value'])||$facet['value']==0) {
			// value used in newer dataset scidata function(flat)
			$vals=$facet['value'];
		} else {
			echo "No values!";debug($facet);exit;
		}
		$errs=null;$unit=null;
		if(!empty($facet['unit'])) { $unit=$facet['unit']; }
		if(!empty($facet['errors'])) { $errs=$facet['errors']; }
		$output=[];
		if($type=='condition') {
			if(!empty($facet['phase'])) {
				$output['phase']=$facet['phase'];
			}
			if(!empty($facet['phase#'])) {
				$output['phase#']=$facet['phase#'];
				$graph['ids'][]=$facet['phase#'];
			}
			if(!empty($facet['quantitykind'])) {
				$output['quantitykind']=$facet['quantitykind'];
			}
			if(!empty($facet['quantitykind#'])) {
				$output['quantitykind#']=$facet['quantitykind#'];
				$graph['ids'][]=$facet['quantitykind#'];
			}
			if(!empty($facet['quantity'])) {
				$output['quantity']=$facet['quantity'];
			}
			if(!empty($facet['quantity#'])) {
				$output['quantity#']=$facet['quantity#'];
				if(stristr($facet['quantity#'],':')) {
					$graph['ids'][]=$facet['quantity#'];
				}
			}
			if(!empty($facet['source'])) {
				$output['source']=$facet['source'];
			}
			if($unit) {
				$output['unit'] = $unit;
			}
			if(!empty($facet['unit#'])) {
				$output['unit#']=$facet['unit#'];
				$graph['ids'][]=$facet['unit#'];
			}
			if(is_array($vals)) {
				$output['valuearray']=[];
				## TODO: implement 'flat' .... then deal with datapoint conditions
				foreach($vals as $vidx=>$val) {
					if(!empty($val['rows'])) {
						$serrows=$val['rows'];
					} else {
						echo "No rows for this condition value!";exit;
					}
					if(isset($val['meta'])) {
						$val=$val['meta'];
					} elseif(isset($val['value'])) {
						$val=$Set->exponentialGen($val['value']);
					}
					$value=[];
					$value['@id']='condition/'.$idx.'/value/'.($vidx+1).'/';
					$value['@type']='sdo:numericValue';
					if($val['isint']) {
						$value['datatype']='xsd:integer';
						$value['number']=(int) $val['scinot'];
					} else {
						$value['datatype']='xsd:float';
						$dp=$val['sf']-($val['exponent']+1);
						$value['number']=number_format($val['scinot'],$dp,'.','');
					}
					$value['sigfigs']=$val['sf'];
					if(!empty($val['unit'])) {
						$value['unit']=$val['unit'];
					}
					if(!empty($errs[$vidx])) {
						$value['error']=$errs[$vidx]['val'];
						if(!is_null($errs[$vidx]['errortype'])) {
							$value['errortype']=$errs[$vidx]['errortype'];
						} else {
							$value['errortype']='unknown';
						}
						if(!is_null($errs[$vidx]['note'])) { $value['errornote']=$errs[$vidx]['note']; }
					} elseif($val['error']!='') {
						$value['error']=$val['error'];
						$value['errortype']='absolute';
						$value['errornote']='estimated from data';
					}
					if($flat) {
						$output=$output+$value;
					} else {
						$output['valuearray'][]=$value;
					}
					foreach($serrows as $serrow) {
						list($ser,$row)=explode(':',$serrow);
						$condrels[$type][$ser][$quant][$row]=$value['@id'];
					}
				}
			} else {
				$val=$vals;
				if($flat) {
					$serrows=$facet['rows'];
					if(isset($val['meta'])) {
						$val=$val['meta'];
					} elseif(isset($val['value'])) {
						$val=$Set->exponentialGen($val['value']);
					} else {
						$val=$Set->exponentialGen($val);
					}
					if($val['isint']) {
						$output['datatype']='xsd:integer';
						$output['number']=(int) $val['scinot'];
					} else {
						$output['datatype']='xsd:float';
						$dp=$val['sf']-($val['exponent']+1);
						$output['number'] = number_format($val['scinot'],$dp,'.','');
					}
					if(!empty($val['sf'])) {
						$output['sigfigs'] = $val['sf'];
					}
					if(!empty($errs)) {
						$output['error']=$errs['val'];
						if(!is_null($errs['errortype'])) {
							$output['errortype']=$errs['errortype'];
						} else {
							$output['errortype']='unknown';
						}
						if(!is_null($errs['note'])) { $output['errnote']=$errs['note']; }
					} elseif($val['error']!='') {
						$output['error']=$val['error'];
						$output['errortype']='absolute';
						$output['errornote']='estimated from data';
					}
					//$condrels[$type][$idx][$quant]=$output;
					foreach($serrows as $serrow) {
						list($ser,$row)=explode(':',$serrow);
						$condrels[$type][$ser][$quant][$row]='condition/'.$idx.'/';
					}
				} else {
					$value=[];
					$value['@id']='condition/'.$idx.'/value/';
					$value['@type']='sdo:numericValue';
					if(isset($val['meta'])) {
						$val=$val['meta'];
					} elseif(isset($val['value'])) {
						$val=$Set->exponentialGen($val['value']);
					} else {
						$val=$Set->exponentialGen($val);
					}
					if($val['isint']) {
						$value['datatype']='xsd:integer';
						$value['number']=(int) $val['scinot'];
					} else {
						$value['datatype']='xsd:float';
						$dp=$val['sf']-($val['exponent']+1);
						$value['number']= number_format($val['scinot'],$dp,'.','');
					}
					if(!empty($errs)) {
						$value['error']=$errs['val'];
						if(!is_null($errs['errortype'])) {
							$value['errortype']=$errs['errortype'];
						} else {
							$value['errortype']='unknown';
						}
						if(!is_null($errs['note'])) { $value['errnote']=$errs['note']; }
					} elseif($val['error']!='') {
						$value['error']=$val['error'];
						$value['errortype']='absolute';
						$value['errornote']='estimated from data';
					}
					$output['value']=$value;
					$condrels[$type][$idx][$quant]=[$value['@id']=>$val];
					//debug($condrels);exit;
				}
			}
			//debug($output);debug($condrels);exit;
		}
		return $output;
	}

	/**
	 * pass metadata through the group
	 * @param array $pnt
	 * @param string $dtype
	 * @param string $prop
	 * @param array|null $olinks
	 * @param array $graph
	 * @return array
	 */
	private function passmeta(array $pnt, string $dtype, string $prop, array $olinks, array &$graph): array
	{
		//debug($pnt);//exit;
		$Qty=ClassRegistry::init('Quantity');
		$val['type']=$dtype;
		if(!empty($pnt['related'])) {
			list($type,)=explode("/",$pnt['related']);
			$val[$type]=$pnt['related'];
		}
		if(!empty($pnt['phase'])) { $val['phase']=$pnt['phase']; }
		if(!empty($pnt['quantitykind'])) { $val['quantitykind']=$pnt['quantitykind']; }
		if(!empty($pnt['quantitykind#'])) { $val['quantitykind#']=$pnt['quantitykind#']; }
		if(!empty($pnt['quantity'])) {
			$val['quantity']=$pnt['quantity'];
		} else {
			$conds=['field like'=>"%'".$prop."'%"];$cont=['Quantitykind'=>'Unit'];
			$pmeta=$Qty->find('first',['conditions'=>$conds,'contain'=>$cont,'recursive'=>-1]);
			debug($pmeta);
			$val['quantity']=$pmeta['Quantity']['name'];
		}
		if(!empty($olinks[$dtype][$prop])) {
			$val['quantity#']=$olinks[$dtype][$prop];
		}
		if(!empty($pnt['quantity#'])) { $val['quantity#']=$pnt['quantity#']; }
		if(!empty($val['quantity#'])) { $graph['ids'][]=$val['quantity#']; }
		if(!empty($pnt['equality'])) { $val['equality']=$pnt['equality']; }
		if(isset($pnt['number'])) { $val['number']=$pnt['number']; }
		if(!empty($pnt['scinot'])) { $val['scinot']=$pnt['scinot']; }
		if(isset($pnt['value'])&&(!empty($pnt['value'])||$pnt['value']==0)) { $val['value'] = $pnt['value']; }  // zero is not false here
		if(isset($pnt['isint'])) { $val['isint']=$pnt['isint']; }
		if(isset($pnt['text'])) { $val['text']=$pnt['text']; }
		if(!empty($pnt['max'])) { $val['max']=$pnt['max']; }
		if(!empty($pnt['sigfigs'])) { $val['sigfigs'] = $pnt['sigfigs']; }
		if(!empty($pnt['dp'])) { $val['dp'] = $pnt['dp']; }
		if(!empty($pnt['unit'])) { $val['unit']=$pnt['unit']; }
		if(!empty($pnt['unit#'])) { $val['unit#']=$pnt['unit#']; }
		if(!empty($pnt['error'])) { $val['error']=$pnt['error']; }
		if(!empty($pnt['errortype'])) { $val['errortype']=$pnt['errortype']; }
		if(!empty($pnt['note'])) { $val['note']=$pnt['note']; }
		return $val;
	}
}
