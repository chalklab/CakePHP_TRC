<?php

//		$type='';
//		if(is_array($sys)&&!empty($sys)) {
//			debug($sys);exit;
//			// System
//			if (count($sys['Substance']) == 1) {
//				$type = "substance";
//			} else {
//				$type = "mixture";
//			}
//			$sid = "substance/1/";
//			$json['toc']['sections'][] = $sid;
//			$mixj['composition']=$sys['composition'];
//			$mixj['phase']=$sys['phase'];
//			$opts = ['name', 'description', 'type'];
//			foreach ($opts as $opt) {
//				if (isset($sys[$opt]) && $sys[$opt] != "") {
//					$mixj[$opt] = $sys[$opt];
//				}
//			}
//			if (isset($sys['Substance'])) {
//				for ($j = 0; $j < count($sys['Substance']); $j++) {
//					// Components
//					unset($subj);
//					$subj['@id'] = $sid."/component/".($j + 1)."/";
//					$subj['@type'] = "sci:chemical";
//					$subj['source'] = "chemical/".($j + 2).'/';
//					$mixj['components'][] = $subj;
//					// Substances
//					unset($subj);$sub = $sys['Substance'][$j];
//					$subj['@id'] = "substance/".($j + 2).'/';
//					$json['toc']['sections'][] = $subj['@id'];
//					$subj['@type'] = "sci:".$sub['type'];
//					$opts = ['name', 'formula', 'molweight'];
//					foreach ($opts as $opt) {
//						if (isset($sub[$opt]) && $sub[$opt] != "") {
//							$subj[$opt] = $sub[$opt];
//						}
//					}
//					if (isset($sub['Identifier'])) {
//						$opts = ['inchi', 'inchikey', 'iupacname','CASRN','SMILES'];
//						foreach ($sub['Identifier'] as $idn) {
//							foreach ($opts as $opt) {
//								if ($idn['type'] == $opt) {
//									$subj[$opt] = $idn['value'];
//								}
//							}
//						}
//					}
//					$sysj['facets'][] = $subj;
//					// Chemicals
//					$chem=$sub['Chemical'];
//					$chmj['@id'] = "chemical/".($j + 2).'/';
//					$json['toc']['sections'][] = $chmj['@id'];
//					$chmj['@type'] = "sci:chemical";
//					$chmj['source'] = "substance/".($j + 2).'/';
//					$chmj['acquired'] = $chem['source'];
//					if(!is_null($chem['purity'])) {
//						$purj['@id'] = "purity/".($j + 2).'/';
//						$purj['@type'] = "sci:purity";
//						$purity=json_decode($chem['purity'],true);
//						foreach($purity as $step) {
//							$stepsj[$step['step']]['@id'] = "step/".$step['step'].'/';
//							$stepsj[$step['step']]['@type'] = "sci:value";
//							$stepsj[$step['step']]['part'] = $step['type'];
//							if(!is_null($step['analmeth'])) {
//								$stepsj[$step['step']]['analysis']=$step['analmeth'];
//							}
//							if(!is_null($step['purimeth'])) {
//								$stepsj[$step['step']]['purification']=$step['purimeth'];
//							} else {
//								$stepsj[$step['step']]['purification']=null;
//							}
//
//							if(!is_null($step['purity'])) {
//								$stepsj[$step['step']]['number']=$step['purity'];
//							}
//							if(!is_null($step['puritysf'])) {
//								$stepsj[$step['step']]['sigfigs']=$step['puritysf'];
//							}
//							if(!is_null($step['purityunit_id'])) {
//								$qudtid=$this->Unit->getfield('qudt',$step['purityunit_id']);
//								$stepsj[$step['step']]['unitref']='qudt:'.$qudtid;
//							}
//							$purj['steps']=$stepsj;
//						}
//						$chmj['purity']=$purj;
//					}
//					$sysj['facets'][] = $chmj;
//				}
//			}
//			$mixj['@id'] = $sid;
//
//			$sysj['facets'][] = $mixj;
//		}
//		debug($sysj);exit;



// System information

// Get chemical system(s)

$systypes=Configure::read('systypes');

// add system facet
$facets['sci:chemicalsystem'][1]=$sys;
// add toc entry
//$toc[]='chemicalsystem/1/';

// add system facet
$facets['sci:chemicalsystem'][1]=$sys;
// add toc entry
//$toc[]='chemicalsystem/1/';

//debug($sys);exit;


// set system rows if defined (mapping of system to rows of data)
if(!empty($sysrows)) {
	$sds->setsysrows($sysrows);
}
?>
