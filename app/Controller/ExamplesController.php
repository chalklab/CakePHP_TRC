<?php

/**
 * Class ExamplesController
 * controller for example in paper
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 12/27/22
 */
class ExamplesController extends AppController
{
	public $uses = ['Scidata'];

	/**
	 * beforeFilter function
	 */
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow('createsd');
	}

	public function createsd()
	{
		// typical data structure coming from the database
		$data =
			['Substance'=> ['name'=>'1-chlorobutane','formula'=>'C4H9Cl','mw'=>'92.57'],
			'Identifier'=>[
				['type'=>'casrn', 'value'=>'109-69-3'],
				['type'=>'pubchemId', 'value'=>'8005'],
				['type'=>'inchikey', 'value'=>'VFWCMGCRMGJXDK-UHFFFAOYSA-N']
				]];
		// debug($data);
		// process data into the correct format for adding to the SciData JSON-LD file
		$sub=[];
		$sub['name'] = $data['Substance']['name'];
		$sub['formula'] = $data['Substance']['formula'];
		$sub['molweight'] = $data['Substance']['mw'];
		foreach($data['Identifier'] as $ident) {
			$sub[$ident['type']] = $ident['value'];
		}
		// debug($sub);
		$example = new $this->Scidata;
		// this context to the semantic meaning of the data in the substance section in the JSON-LD file
		$example->setcontexts(["https://stuchalk.github.io/scidata/contexts/crg_substance.jsonld"]);
		// this namespace is to indicate that any value starting with "sub:" is from this ontology
		$example->setnspaces(['sub'=>'https://stuchalk.github.io/scidata/ontology/substance.owl#']);
		// the next two settings add the @base at the top of the file and the @id underneath @graph
		// needed to create uris from the internal document structure
		// see: https://www.w3.org/TR/json-ld/#base-iri
		$example->setbase("https://sds.coas.unf.edu/trc/examples/createsd/");
		$example->setgraphid("https://sds.coas.unf.edu/trc/examples/createsd/");
		// add the substance data to the facet section of the SciData JSON-LD document
		$facets['sub:substance'] = [$sub];
		$example->setfacets($facets);
		// output the data as JSON-LD for download
		$jld=$example->asjsonld(true);
		header("Content-Type: application/ld+json");
		header('Content-Disposition: attachment; filename="example.jsonld"');
		echo $jld;exit;
	}

}
