<?php
// code from FilesController to process ReactionData
// not many files in JCED with data (125)
// started to integrate into DB (reactions, participants, datasets tables)
// found data in some files (the first few) that did not seem to have temperature data
// or match the data in the paper at all (check papers and then call NIST)
// needs a lot of work...

if (isset($trc['ReactionData'])) {
	$sets = $trc['ReactionData'];
	if (!isset($sets[0])) { $sets = [0 => $sets]; }
	foreach ($sets as $setidx => $set) {
		// add reaction
		$cnds=['dataset_id'=>$setid,'type'=>$set['eReactionType'],'rctnum'=>$set['nReactionDataNumber'],
			'partcnt'=>count($set['Participant'])];
		$rctid=$this->NewReaction->add($cnds);

		// add participants
		$parts = $set['Participant'];
		if (!isset($parts[0])) { $parts = ['0' => $parts]; }
		// chmids and $subisds are indexed by orgnum
		$prtids=[];
		foreach($parts as $pidx=>$part) {
			$phase=$this->NewPhasetype->find('list',['fields'=>['name','id'],'conditions'=>['name'=>$part['ePhase']]]);
			$orgnum=$part['RegNum']['nOrgNum'];
			$coef=$part['nStoichiometricCoef'];
			$cnds = ['reaction_id' => $rctid, 'chemical_id' => $chmids[$orgnum],'partnum'=> ($pidx+1),
				'substance_id' => $subids[$orgnum], 'coef' => $coef, 'phase_id' => $phase[$part['ePhase']]];
			$prtid=$this->NewParticipant->add($cnds);
			$prtids[$orgnum]=$prtid;
		}

		// add dataset
		$temp = ['Dataset' => ['title' => 'Reaction dataset ' . ($setidx + 1) . ' in paper ' . $doi,
			'file_id' => $fid, 'system_id' => $sysid, 'reference_id' => $refid, 'phase' => json_encode($phase)]];
		debug($prtids);exit;

		$this->Dataset->create();
		$this->Dataset->save($temp);
		$setid = $this->Dataset->id;

		// Create dataseries
		$temp = ['Dataseries' => ['dataset_id' => $setid, 'type' => 'independent set']];
		$this->Dataseries->create();
		$this->Dataseries->save($temp);
		$serid = $this->Dataseries->id;

		// Get the properties
		$type = $set['eReactionType'];
		$props = $set['Property'];
		$condarray = [];
		$proparray = [];
		if (!isset($props[0])) {
			$props = ['0' => $props];
		}
		foreach ($props as $prop) {
			$number = $prop['nPropNumber'];
			$group = $prop['Property-MethodID']['PropertyGroup'];
			if (isset($group['ReactionStateChangeProp'])) {
				$proptype = $group['ReactionStateChangeProp'];
				$propgroup = 'ReactionStateChangeProp';
			} elseif (isset($group['ReactionEquilibriumProp'])) {
				$proptype = $group['ReactionEquilibriumProp'];
				$propgroup = 'ReactionEquilibriumProp';
			}
			$propname = $proptype['ePropName'];
			$unitid = $this->getunit($propname);
			if (isset($proptype['sMethodName'])) {
				$methname = $proptype['sMethodName'];
			} else {
				$methname = null;
			}
			$conditions = []; // Reaction conditions...
			if (isset($prop['Solvent'])) {
				if (is_array($prop['Solvent'])) {
					$solvent = json_encode($prop['Solvent']);
				} else {
					$solvent = $prop['Solvent'];
				}
			} else {
				$solvent = null;
			}
			if (isset($prop['Catalyst'])) {
				if (is_array($prop['Catalyst'])) {
					$catalyst = json_encode($prop['Catalyst']);
				} else {
					$catalyst = $prop['Catalyst'];
				}
			} else {
				$catalyst = null;
			}
			if (isset($prop['eStandardState'])) {
				$standardstate = $prop['eStandardState'];
			} else {
				$standardstate = null;
			}
			if (isset($prop['nTemperature-K'])) {
				$e = $this->exponentialGen($prop['nTemperature-K']);
				$temp = ['Condition' => ['datapoint_id' => null, 'property_name' => 'temperature', 'property_id' => 3,
					'number' => $prop['nTemperature-K'], 'unit_id' => 5,
					'accuracy' => $prop['nTemperatureDigits'], 'significand' => $e['significand'],
					'exponent' => $e['exponent']]];
				$condarray[] = $temp;
			}
			if (isset($prop['nPressure-kPa'])) {
				$e = $this->exponentialGen($prop['nPressure-kPa']);
				$temp = ['Condition' => ['datapoint_id' => null, 'property_name' => 'pressure', 'property_id' => 2,
					'number' => $prop['nPressure-kPa'], 'unit_id' => 25,
					'accuracy' => $prop['nPressureDigits'], 'significand' => $e['significand'],
					'exponent' => $e['exponent']]];
				$condarray[] = $temp;
			}
			if (isset($prop['PropDeviceSpec']['eDeviceSpecMethod'])) {
				$specmethod = $prop['PropDeviceSpec']['eDeviceSpecMethod'];
			} else {
				$specmethod = null;
			}
			// Get Property from sampleprop
			$propid = $this->Property->getfield('id', ['field like' => '%"' . $propname . '"%']);
			$temp = ["Reactionprop" => ['dataset_id' => $setid, 'number' => $number, 'type' => $type,
				'property_id' => $propid, 'property_group' => $propgroup, 'property_name' => $propname,
				'method_name' => $methname, 'reaction' => json_encode($reaction), 'solvent' => $solvent,
				'catalyst' => $catalyst, 'standardstate' => $standardstate, 'devicespecmethod' => $specmethod]];
			$this->Reactionprop->create();
			$this->Reactionprop->save($temp);
			$propid = $this->Reactionprop->id;
			//$propid=0;
			$proparray[$number] = $propid . ":" . $unitid;
		}

		// Series conditions
		if (isset($set['Constraint'])) {
			$serconds = $set['Constraint'];
			if (!isset($serconds[0])) {
				$serconds = [0 => $serconds];
			}
			foreach ($serconds as $sercond) {
				$ctype = $sercond['ConstraintID']['ConstraintType'];
				$res = $this->getpropunit($ctype);
				list($propname, $unitid) = explode(":", $res);
				$number = $sercond['nConstraintValue'];
				$sf = $sercond['nConstrDigits'];
				$temp = ['Condition' => ['dataseries_id' => $serid, 'property_name' => $propname, 'number' => $number, 'unit_id' => $unitid, 'accuracy' => $sf]];
				$this->Condition->create();
				$this->Condition->save($temp);
			}
		}

		// Grab the data
		$data = $set['NumValues'];
		if (!isset($data[0])) {
			$data = [0 => $data];
		}
		foreach ($data as $idx => $datum) {
			// Add datapoint
			$temp = ['Datapoint' => ['dataseries_id' => $serid, 'row_index' => ($idx + 1)]];
			$this->Datapoint->create();
			$this->Datapoint->save($temp);
			$pntid = $this->Datapoint->id;

			// Add conditions
			foreach ($condarray as $cond) {
				$cond['Condition']['datapoint_id'] = $pntid;
				$this->Condition->create();
				$this->Condition->save($cond);
				$this->Condition->clear();
			}

			// Add data
			$edata = $datum['PropertyValue'];
			if (!isset($edata[0])) {
				$edata = [0 => $edata];
			}
			foreach ($edata as $edatum) {
				$propunit = $proparray[$edatum['nPropNumber']];
				list($rpropid, $unitid) = explode(":", $propunit);
				$number = $edatum['nPropValue'];
				$acc = $edatum['nPropDigits'];
				if (isset($edatum['PropRepeatability'])) {
					$err = $edatum['PropRepeatability']['nPropRepeatValue'];
				} else {
					$err = null;
				}
				$rprop = $this->Reactionprop->find('first', ['conditions' => ['id' => $rpropid], 'recursive' => -1]);
				$propid = $rprop['Reactionprop']['property_id'];
				$e = $this->exponentialGen($number);
				$temp = ['Data' => ['datapoint_id' => $pntid, 'property_id' => $propid,
					'reactionprop_id' => $rpropid, 'number' => $number, 'unit_id' => $unitid, 'error' => $err,
					'accuracy' => $acc, 'significand' => $e['significand'], 'exponent' => $e['exponent']]];
				$this->Data->create();
				$this->Data->save($temp);
				$this->Data->clear();
			}
		}
	}
}
