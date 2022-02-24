<?php

/**
 * Class Dataset
 * model for the datasets table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class Dataset extends AppModel
{
	// relationships to other tables
	// mixtures, dataseries and sampleprop tables marked as dependent, so they get deleted when a dataset does
	public $hasOne = [
		'Mixture'=> [
			'foreignKey' => 'dataset_id',
			'dependent' => true
		]
	];
	public $hasMany = [
        'Dataseries'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ],
        'Sampleprop'=> [
            'foreignKey' => 'dataset_id',
            'dependent' => true
        ]
    ];
	public $hasAndBelongsToMany = ['Chemical'];
    public $belongsTo = ['File','Report','System','Reference'];

	public $virtualFields = [
		'fileset'=>"CONCAT(Dataset.file_id,':',Dataset.setnum)",
		'filesys'=>"CONCAT(Dataset.file_id,':',Dataset.system_id)",
		'refsetnum'=>"CONCAT(Dataset.reference_id,':',Dataset.setnum)",
	];

	/**
	 * function to add a new dataset if it does not already exist
	 * @param array $data
	 * @param $setcnt
	 * @return integer
	 * @throws
	 */
	public function add(array $data,&$setcnt): int
	{
		return $this->addentry('Dataset',$data);
	}

	/**
	 * Generates an exponential number removing any zeros at the end not needed
	 * @param $string
	 * @return array
	 */
	public function exponentialGen($string): array
	{
		$e="e";$return=[];
		$return['text']=$string;
		$return['value']=floatval($string);
		$return['isint']=1;
		if(stristr($string,'.')) { $return['isint']=0; }
		if($string==0) {
			$return+=['dp'=>0,'scinot'=>'0e+0','exponent'=>0,'significand'=>0,'error'=>null,'sf'=>0];
		} elseif(stristr($string,'E')) {
			$string=str_replace('E','e',$string); // so it catches either case
			list($man, $exp)=explode('e',$string);
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
			if(stristr($num[0],'-')) { $neg=true; }
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
					// exponent is based on digit before the decimal -1
				} else {
					// Remove any leading zeroes after decimal and count string length
					$return['sf']=strlen(ltrim($num[1],'0'));
					// Count leading zeros
					preg_match('/^(0*)[1234567890]+$/',$num[1],$match);
					$return['exponent']=-1*(strlen($match[1]) + 1);
				}
				$return['scinot']=sprintf("%.".($return['sf']-1).$e, $string);
				$s=explode($e,$return['scinot']);
				$return['significand']=$s[0];
				$return['error']=pow(10,$return['exponent']-$return['sf']+1);
			} else {
				$return['dp']=0;
				$return['scinot']=sprintf("%.".(strlen($string)-1).$e, $string);
				$s=explode($e,$return['scinot']);
				$return['significand']=$s[0];
				$return['exponent'] = $s[1];
				$z=explode(".", $return['significand']);
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
