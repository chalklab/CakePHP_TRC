<?php
// Store all project configuration parameters here

$config['server']="https://chalk.coas.unf.edu";
$config['path']="/trc";
$config['filepath']="files/pdf/";
$config['textfilepath']="files/text/";
$config['xmlfilepath']="files/xml/";
$config['jsonfilepath']="files/json/";
$config['xsltfilepath']="files/xslt/";
$config['pdftotextPath']['mac']=DS.'opt'.DS.'local'.DS.'bin'.DS.'pdftotext';
$config['pdftotextPath']['freebsd']=DS.'usr'.DS.'local'.DS.'bin'.DS.'pdftotext';
$config['pdftotextPath']['linux']='pdftotext';
$config['pdftotextPath']['windows']=WWW_ROOT.'exec'.DS.'pdftotext'.DS.'pdftotext.exe';
$config['textReplacementArray']=["–"=>"-","−"=>"-"]; //array of replacement characters for text cleanup
$config['github']['token']="7ff877e0626ae494d9addfe3e37c8f5bfacf0b32";

$config['filetypes']=['pdf'=>'PDF','txt'=>'Text','xml'=>'XML','html'=>'HTML'];

$config['scidata']['selectlist']=['condition'=>'Condition data',
    'annotation'=>'Annotation',
    'chemical'=>'Chemical identifier',
    'chemprop'=>'Chemical property data',
    'data'=>'Experimental data',
    'equation'=>'Equation',
    'eqnterm'=>'Equation constant',
    'eqnvariable'=>'Equation variable',
    'eqnvariablelimit'=>'Equation variable limit',
    'eqnoperator'=>'Equation operator',
    'eqnprop'=>'Equation result property',
    'eqnpropunit'=>'Equation result unit',
    'error'=>'Experimental data error',
    'propheader'=>'Property Header',
    'reference'=>'Reference/Citation',
    'series'=>'Series identifier',
    'seriescond'=>'Series condition data',
    'setting'=>'Series setting data',
    'suppdata'=>'Supplmental/derived data',
    'to_be_set'=>'(Set as part of rule)'
    ];
$config['scidata']['list']=array_keys($config['scidata']['selectlist']);

$config['journal']['abbrevs']=[
    'Thermochim. Acta'=>'tca',
    'J. Chem. Thermodyn.'=>'jct',
    'Int. J. Thermophys.'=>'ijt',
    'J. Chem. Eng. Data'=>'jced',
    'Fluid Phase Equilib.'=>'fpe'
];
// Jmol configuration parameters
$config['jmol']['j2spath']="/js/jsmol/j2s";
$config['jmol']['color']="#E0E0E0";
$config['jmol']['height']=190;
$config['jmol']['width']=190;

// Online data repository settings
$config['cir']['url']="https://cactus.nci.nih.gov/chemical/structure/<id>/file?format=sdf&get3d=True";