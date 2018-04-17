<?php

/**
 * Class Scidata
 * Scidata model
 */
class Scidata extends AppModel
{

    public $useTable=false;

    public $path=null;
    public $context="https://stuchalk.github.io/scidata/contexts/scidata.jsonld";
    public $aliases=[
        'sci'=>'http://stuchalk.github.io/scidata/ontology/scidata.owl#',
        'dc'=>'http://purl.org/dc/terms/',
        'qudt'=>'http://www.qudt.org/qudt/owl/1.0.0/unit.owl#',
        'xsd'=>'http://www.w3.org/2001/XMLSchema#'];
	public $pid=null;
	public $base=null;
    public $meta=null;
    public $authors=null;
    public $related=null;
	public $startdate=null;
	public $permalink=null;
	public $toc=null;
    public $discipline=null;
    public $subdiscipline=null;
	public $facets=null;
	public $aspects=null;
    public $data=null;
    private $cpds=[];
    private $syss=[];
    private $chms=[];
    private $sets=[];
    private $sers=[];
    private $pnts=[];
    private $cnds=[];
    private $scnds=[];
    private $sttgs=[];
    public $sources=null;
    public $rights=null;
    public $errors=null;
    public $output=null;


    /**
     * Class Constructor
     * @param array|bool|int|string $file
     */
    public function __construct()
    {
        parent::__construct();
        $output=[];
        $output['@context']=[];
        $output['@id']="";
        $output['pid']="";
        $output['title']="";
        $output['authors']=[];
        $output['description']="";
        $output['publisher']="";
        $output['startdate']="";
        $output['permalink']="";
        $output['related']=[];
        $output['toc']=[];
        $output['scidata']=[];
        $output['sources']=[];
        $output['rights']=[];

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
        }}

    // Setters

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
	 * Set the pid
	 * @param $value
	 * @return bool
	 */
	public function setpid($value=null) {
		if($value==null) {
			return false;
		} else {
			return $this->setter("pid","string",$value);
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
	 * Set the facets
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
	 * Generic setter method
	 * @param $prop
	 * @param $type
	 * @param $value
	 * @return bool
	 */
	private function setter($prop,$type,$value) {
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
		$this->$prop=$value;
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
     * @return array
     */
    public function asarray()
    {
        $output=$this->output;
        $output['@context']=[
			$this->context,
			$this->aliases,
        	['@base'=>$this->base]];
        
        // base
        if(!is_null($this->pid)) {
			$output["pid"]=$this->pid;
		} else {
        	unset($output["pid"]);
		}
		
		// meta
		if(!is_null($this->meta)) {
            if(isset($this->meta['title'])) 		{ $output['title']=$this->meta['title']; }
            if(isset($this->meta['description'])) 	{ $output['description']=$this->meta['description']; }
            if(isset($this->meta['publisher'])) 	{ $output['publisher']=$this->meta['publisher']; }
        }
        
        // authors
		if(!is_null($this->authors)) {
			foreach ($this->authors as $idx=>$au) {
				$output['authors'][]=['@id'=>'author/'.($idx+1).'/','@type'=>'dc:creator','name'=>$au];
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
			$output["startdate"]=$date;
		} else {
			unset($output["startdate"]);
		}
		
		// permalink
		if(!is_null($this->permalink)) {
			$output["permalink"]=$this->permalink;
		} else {
			unset($output["permalink"]);
		}
		
		// scidata
	
		// SciData
		$sci=[];
		$sci['@id']="scidata";
		$sci['@type']="sci:scientificData";
		$sci['discipline']=$this->discipline;
		$sci['subdiscipline']=$this->subdiscipline;
		$sci['methodology']=[];
		$sci['system']=[];
		$sci['dataset']=[];
	
		// methodology
		$meth=[];
		$meth['@id']='methodology/';
		$meth['@type']='sci:methodology';
		$meth['aspects']=[];
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
			}
		}
		$sci['methodology']=$meth;
		
		// system
		$sys=[];
		$sys['@id']='system/';
		$sys['@type']='sci:system';
		$sys['facets']=[];
		foreach($this->facets as $type=>$facgrp) {
			$fac=[];$ns="";
			if(stristr($type,":")) {
				list($ns,$type)=explode(":",$type);
				$ns.=":";
			}
			foreach($facgrp as $idx=>$facet) {
				$fac["@id"]=$type."/".$idx.'/';
				$fac["@type"]=$ns.$type;
				foreach($facet as $label=>$items) {
					if(is_array($items)) {
						foreach($items as $idx=>$item) {
							if(is_array($item)) {
								$id=$label.'/'.($idx+1).'/';
								$itype='sci:'.$label;
								$item=["@id"=>$id,"@type"=>$itype]+$item;
								$items[$idx]=$item;
							}
						}
						$facet[$label]=$items;
					}
				}
				$sys['facets'][]=array_merge($fac,$facet);
			}
		}
		$sci['system']=$sys;
		
		// data
		// foreach dataset...
		$datasets=$this->data;
		$sertot=0;$pnttot=0;
		foreach($datasets['dataset'] as $setidx=>$set) {
			$set=["@id"=>'dataset/'.($setidx+1).'/',"@type"=>"sci:dataset"]+$set;
			foreach($set['dataseries'] as $seridx=>$ser) {
				$ser=["@id"=>'dataseries/'.($sertot+$seridx+1).'/',"@type"=>"sci:dataseries"]+$ser;
				foreach($ser['datapoints'] as $pntidx=>$pnt) {
					$pnt=["@id"=>'datapoint/'.($pnttot+$pntidx+1).'/',"@type"=>"sci:datapoint"]+$pnt;
					$pnt['value']=["@id"=>'value/'.($pnttot+$pntidx+1).'/',"@type"=>"sci:value"]+$pnt['value'];
					$ser['datapoints'][$pntidx]=$pnt;
				}
				$set['dataseries'][$seridx]=$ser;
				$pnttot=count($ser['datapoints'])+$pnttot;
			}
			$sertot=count($set['dataseries'])+$sertot;
			$sci['dataset'][$setidx]=$set;
		}
		
		$output['scidata']=$sci;
		
		// sources
		if(!is_null($this->sources)) {
        	$srcs=[];
        	if(isset($this->sources['citation'])) {
				$srcs[]=$this->sources;
			} else {
				$srcs=$this->sources;
			}
			foreach($srcs as $idx=>$src) {
				$source=[];
				$source["@id"]='source/'.($idx+1).'/';
				$source["@type"]="sci:source";
				if(isset($src['journal']))  { $source["journal"]=$src['journal']; }
				if(isset($src['citation'])) { $source["citation"]=$src['citation']; }
				if(isset($src['url'])) 		{ $source["url"]=$src['url']; }
				if(isset($src['type'])) 	{ $source["type"]=$src['type']; }
				$output["sources"][]=$source;
			}
		} else {
			unset($output["sources"]);
		}
	
		// rights
		if(!is_null($this->rights)) {
        	$rights=[];
			$rights["@id"]='rights/1/';
			$rights["@type"]="sci:rights";
			$output["rights"]=array_merge($rights,$this->rights);
		} else {
			unset($output["rights"]);
		}
	
	
		$this->output=$output;
        return $this->output;
    }

    /**
     * create output and return as json
     * @return string
     */
    public function asjsonld()
    {
        $output=$this->asarray();
        return json_encode($output,JSON_UNESCAPED_UNICODE);
    }

}