<?php

// extra code from api.works
if($all==1000000) {
	$all=$papers['message']['total-results'];
	echo "<p><b>Hits: ".$all."</b></p>";
}
echo "OFFSET: ".$offset."<br />";
foreach($papers['message']['items'] as $paper) {
	//debug($paper);exit;
	$exist=$Cite->find('first',['conditions'=>['url'=>$paper['DOI']]]);
	if(!empty($exist)) { echo "Aready have....".$paper['DOI']."<br />";continue; } // Continue if already have DOI
	$baddoi=$Bad->find('first',['conditions'=>['doi'=>$paper['DOI']]]);
	if(!empty($baddoi)) { echo "Found bad DOI....".$paper['DOI']."<br />";continue; } // Continue if DOI is bad
	$new=$New->find('first',['conditions'=>['url'=>$paper['DOI']]]);
	if(!empty($new)) { echo "Found in new....".$paper['DOI']."<br />";continue; } // Continue if DOI is in new citations
	$term=str_replace("%20"," ",$term);
	if(!stristr($paper['title'][0],$term)&&!stristr($paper['title'][0],str_replace(" ","-",$term))) {
		$Bad->create();
		$Bad->save(['Baddois'=>['doi'=>$paper['DOI'],'title'=>$paper['title'][0]]]); // Save bad doi
		$Bad->clear();
		echo "Added as bad DOI....".$paper['DOI']."<br />";continue;
	}
	//debug($paper);
	$add=[];
	$add['title']=ucwords($paper['title'][0]);
	$austr=$auwebstr="";
	if(isset($paper['author'])) {
		foreach($paper['author'] as $author) {
			$family=ucwords(strtolower($author['family']));
			$names=explode(" ",$author['given']);
			$inits="";
			foreach($names as $name) {
				$inits.=$name[0].".";
			}
			$austr.=$family.", ".$inits.';';
			$auwebstr.=$author['given']." ".$family.",";
		}
		$austr=substr($austr,0,-1);
		$auwebstr=substr($auwebstr,0,-1);
		// Primary author
		if(count($paper['author'])==1) {
			// Find in authors table
			$au=$A->find('first',['conditions'=>['OR'=>[['abbrev'=>$austr],['abbrev'=>str_replace("-", " ",$austr)]]],'fields'=>['id']]);
			if(empty($au)) {
				// Add new pauthor
				$A->create();
				$pau=['name'=>$auwebstr,'abbrev'=>$austr,'first_name'=>$paper['author'][0]['given'],'lastname'=>$paper['author'][0]['family']];
				$A->save(['Pauthors'=>$pau]);
				$auid=$A->id;
				$A->clear();
			} else {
				$auid=$au['Pauthors']['id'];
			}
			$add['pauthor_id']=$auid;
			$add['pauthor']=$austr;
		}
	}
	$add['authors']=$austr;
	$add['authorsweb']=$auwebstr;
	if(isset($paper['volume'])) { $add['volume']=ucwords($paper['volume']); }
	if(isset($paper['page'])) {
		if (!stristr($paper['page'], "-")) {
			list($add['startpage']) = $paper['page'];
		} else {
			list($add['startpage'], $add['endpage']) = explode("-", $paper['page']);
		}
	}
	if(isset($paper['volume'])) { $add['issue']=ucwords($paper['issue']); }
	if(isset($paper['container-title'][1])) {
		$add['journal']=ucwords($paper['container-title'][1]);
	} else {
		$add['journal']=ucwords($paper['container-title'][0]);
	}
	$add['url']=ucwords($paper['DOI']);
	$add['urltype']='doi';
	$add['refcount']=ucwords($paper['reference-count']);
	// Check if publisher is in the system yet
	list($doiprefix,)=explode("/",$paper['DOI']);
	$pub=$Pub->find('list',['fields'=>['doiprefix','id'],'conditions'=>['doiprefix like'=>'%'.$doiprefix.'%']]);
	if(empty($pub)) {
		// Add publisher to the system
		echo "Adding Publisher: ".$paper['publisher']."<br />";
		$Pub->create();
		$Pub->save(['Publishers'=>['name'=>$paper['publisher'],'doiprefix'=>$doiprefix]]);
		$pubid=$Pub->id;
		$Pub->clear();
	} else {
		$pubid=$pub[$doiprefix];
	}
	// Check if journal is in the system yet
	$jnlid="";$issn="";
	// Check ISSNs
	if(isset($paper['ISSN'])) {
		foreach ($paper['ISSN'] as $issn) {
			$jnl = $J->find('list', ['conditions' => ['issn' => $issn], 'fields' => ['issn', 'id']]);
			if (!empty($jnl)) {
				$jnlid = $jnl[$issn];
				$issn = $jnlid;
				break;
			}
		}
	}
	// Check journal title
	if($jnlid=="") {
		if(isset($paper['container-title'][0])) {
			$jnl=$J->find('list',['conditions'=>['name'=>$paper['container-title'][0]],'fields'=>['name','id']]);
			if(!empty($jnl)) {
				$jnlid=$jnl[$paper['container-title'][0]];
			}
		}
	}
	// Check journal abbreviation
	if($jnlid=="") {
		if(isset($paper['container-title'][1])) {
			$jnl=$J->find('list',['conditions'=>['abbrev'=>$paper['container-title'][1]],'fields'=>['abbrev','id']]);
			if(!empty($jnl)) {
				$jnlid=$jnl[$paper['container-title'][1]];
			}
		}
	}
	// Add journal if not found
	if($jnlid=="") {
		echo "Adding Journal: ".$paper['container-title'][0]."<br />";
		$J->create();
		(isset($paper['container-title'][1])) ? $abbrev=$paper['container-title'][1] : $abbrev="";
		$J->save(['Journals'=>['name'=>$paper['container-title'][0],'abbrev'=>$abbrev,'issn'=>$issn,'publisher_id'=>$pubid]]);
		$jnlid=$J->id;
		$J->clear();
	}
	$add['journal_id']=$jnlid;
	$add['year']=$paper['issued']['date-parts'][0][0];
	$add['keywords']="";
	if(isset($paper['subject'])) {
		foreach($paper['subject'] as $sub) {
			$add['keywords'].=$sub.";";
		}
	}
	//debug($add);exit;
	$New->create();
	$New->save(['Newcites'=>$add]);
	$New->clear();
	echo "New....".$paper['DOI']."<br />";
	debug($add);
}
?>