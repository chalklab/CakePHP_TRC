-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 08, 2023 at 03:51 PM
-- Server version: 5.7.42
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stuchalk_trc`
--

-- --------------------------------------------------------

--
-- Table structure for table `chemicals`
--

CREATE TABLE `chemicals` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL,
  `file_id` smallint(5) UNSIGNED ZEROFILL NOT NULL,
  `substance_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL,
  `orgnum` tinyint(3) UNSIGNED NOT NULL,
  `sourcetype` enum('Commercial source','Synthesized by the authors','Synthesized by others','Standard Reference Material (SRM)','Isolated from a natural product','Not stated in the document','No sample used') COLLATE utf8mb4_bin DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of chemicals from ThermoML files';

-- --------------------------------------------------------

--
-- Table structure for table `chemicals_datasets`
--

CREATE TABLE `chemicals_datasets` (
  `id` mediumint(8) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `chemical_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {chemicals}',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {datasets}',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Join table for chemicals and datasets';

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key',
  `chemical_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {chemicals}',
  `mixture_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {mixtures}',
  `compnum` tinyint(2) UNSIGNED DEFAULT NULL COMMENT 'component index',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `conditions`
--

CREATE TABLE `conditions` (
  `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {datasets}',
  `dataseries_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {dataseries}',
  `datapoint_id` mediumint(7) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {datapoints}',
  `quantity_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {quantities}',
  `system_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {systems}',
  `component_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {component}',
  `phase_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {phases}',
  `number` tinytext COLLATE utf8mb4_bin COMMENT 'numeric value in sci. not. as text',
  `significand` tinytext COLLATE utf8mb4_bin COMMENT 'significand as text',
  `exponent` tinytext COLLATE utf8mb4_bin COMMENT 'exponent as text',
  `unit_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {units}',
  `accuracy` tinyint(2) DEFAULT NULL COMMENT '# of sig. digits as int',
  `exact` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'exact value (boolean)',
  `text` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'text of numeric value from XML',
  `issue` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'any issue with data?',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `crosswalks`
--

CREATE TABLE `crosswalks` (
  `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL,
  `context_id` smallint(5) UNSIGNED ZEROFILL NOT NULL,
  `dataset_id` smallint(5) UNSIGNED ZEROFILL NOT NULL,
  `table` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `field` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `ontterm_id` int(5) UNSIGNED ZEROFILL NOT NULL,
  `sdsection` enum('metadata','methodology','system','dataset','other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `sdsubsection` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of crosswalk data for the ChEMBL data';

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `id` mediumint(8) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {datasets}',
  `dataseries_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {dataseries}',
  `datapoint_id` mediumint(7) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {datapoints}',
  `quantity_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {quantities}',
  `sampleprop_id` mediumint(7) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {sampleprops}',
  `component_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {components}',
  `phase_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {phases}',
  `number` tinytext COLLATE utf8mb4_bin COMMENT 'numeric value in sci. not. as text',
  `significand` tinytext COLLATE utf8mb4_bin COMMENT 'significand as text',
  `exponent` tinytext COLLATE utf8mb4_bin COMMENT 'exponent as text',
  `error` tinytext COLLATE utf8mb4_bin COMMENT 'uncertainty as text',
  `error_type` enum('absolute','relative','SD','%RSD','CI') COLLATE utf8mb4_bin NOT NULL DEFAULT 'absolute' COMMENT 'uncertainty type as enum',
  `unit_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {units}',
  `accuracy` tinyint(2) DEFAULT NULL COMMENT '# of sig. digits as int',
  `exact` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'exact value (boolean)',
  `text` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'text of numeric value from XML',
  `issue` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'any issue with data?',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `datapoints`
--

CREATE TABLE `datapoints` (
  `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {datasets}',
  `dataseries_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {dataseries}',
  `row_index` varchar(10) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'index of point in series',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `dataseries`
--

CREATE TABLE `dataseries` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {datasets}',
  `idx` smallint(5) UNSIGNED NOT NULL COMMENT 'index of series in dataset',
  `points` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'number of points in series',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Dataseries are part of a dataset and are made up of data' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `datasets`
--

CREATE TABLE `datasets` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `title` varchar(512) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'title [series x in DOI]',
  `setnum` smallint(3) UNSIGNED DEFAULT NULL COMMENT 'index of set in XML file',
  `file_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {files}',
  `report_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {reports}',
  `system_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {systems}',
  `reference_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {references}',
  `trcidset_id` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'unique id from <trcrefid> and setnum',
  `points` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'number of points in dataset',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Sets of property data about a chemical system' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `trcid` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'unique id from <trcrefid> in XML',
  `abstract` varchar(4096) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'abstract of paper',
  `date` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'date of publication',
  `year` year(4) DEFAULT NULL COMMENT 'year of publication',
  `reference_id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {references}',
  `filename` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'XML file name',
  `points` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'number of points in file',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='TRC Thermoml files';

-- --------------------------------------------------------

--
-- Table structure for table `identifiers`
--

CREATE TABLE `identifiers` (
  `id` mediumint(8) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `substance_id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {substances}',
  `type` enum('inchi','inchikey','casrn','smiles','chemspiderId','pubchemId','iupacname','springerId','othername','csmiles','ismiles','wikidataId','dsstoxcmpId','ecnumber','chemblId') COLLATE utf8mb4_bin NOT NULL COMMENT 'type of identifier (enum)',
  `value` varchar(1024) COLLATE utf8mb4_bin NOT NULL COMMENT 'identifier value as text',
  `source` varchar(8) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'identifier source as text',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='types of information about a substance that is used';

-- --------------------------------------------------------

--
-- Table structure for table `journals`
--

CREATE TABLE `journals` (
  `id` tinyint(3) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(256) COLLATE utf8mb4_bin NOT NULL COMMENT 'name of journal',
  `coden` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'journal CODEN id',
  `issn` varchar(9) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'journal ISSN',
  `set` varchar(8) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'code to identify journal file set',
  `language` set('Unknown','Afrikaans','Arabic','Chinese','Czech','Danish','Dutch','English','Finnish','French','German','Hungarian','Italian','Japanese','Korean','Norwegian','Polish','Portuguese','Romanian','Russian','Slovak','Spanish','Swedish','Taiwanese','Turkish') COLLATE utf8mb4_bin NOT NULL DEFAULT 'Unknown' COMMENT 'journal language',
  `abbrev` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'name abbreviation',
  `publisher` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'publisher',
  `homepage` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'homepage (URL)',
  `doiprefix` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'prefix of doi for journal',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='FAD Journals';

-- --------------------------------------------------------

--
-- Table structure for table `keywords`
--

CREATE TABLE `keywords` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `report_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {reports}',
  `term` varchar(512) COLLATE utf8mb4_bin NOT NULL COMMENT 'keyword',
  `check` tinyint(1) DEFAULT '0' COMMENT 'checked (boolean)',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of keywords in XML files';

-- --------------------------------------------------------

--
-- Table structure for table `mixtures`
--

CREATE TABLE `mixtures` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `system_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {systems}',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {datasets}',
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `nspaces`
--

CREATE TABLE `nspaces` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ns` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `homepage` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of ontology namespaces';

-- --------------------------------------------------------

--
-- Table structure for table `ontterms`
--

CREATE TABLE `ontterms` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL,
  `title` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `definition` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `nspace_id` smallint(5) UNSIGNED ZEROFILL NOT NULL,
  `sdsection` enum('metadata','methodology','system','dataset','other') COLLATE utf8_unicode_ci DEFAULT NULL,
  `sdsubsection` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Ontological metadata elements';

-- --------------------------------------------------------

--
-- Table structure for table `phases`
--

CREATE TABLE `phases` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `mixture_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {mixtures}',
  `phasetype_id` tinyint(2) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {phasetypes}',
  `orgnum` tinyint(2) UNSIGNED DEFAULT NULL COMMENT '<orgnum> for substance',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Join table for datasets and phases';

-- --------------------------------------------------------

--
-- Table structure for table `phasetypes`
--

CREATE TABLE `phasetypes` (
  `id` tinyint(2) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(64) COLLATE utf8mb4_bin NOT NULL COMMENT 'descriptive name of phase',
  `type` enum('solid','liquid','gas','fluid','liquid crystal') COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'phase type (enum)',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='TRC phase options (from schema)';

-- --------------------------------------------------------

--
-- Table structure for table `purificationsteps`
--

CREATE TABLE `purificationsteps` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `chemical_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {chemicals}',
  `step` tinyint(3) UNSIGNED NOT NULL COMMENT 'step number',
  `type` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'step type',
  `purity` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'value as text',
  `puritysf` tinyint(2) UNSIGNED DEFAULT NULL COMMENT 'purity sig. digits',
  `purityunit_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {units}',
  `analmeth` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'analysis method',
  `purimeth` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL COMMENT 'purification method',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of purification steps for a chemical';

-- --------------------------------------------------------

--
-- Table structure for table `quantities`
--

CREATE TABLE `quantities` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'name',
  `phase` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'phase',
  `field` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'XML field',
  `label` varchar(64) COLLATE utf8mb4_bin NOT NULL COMMENT 'label',
  `group` enum('Criticals','VaporPBoilingTAzeotropTandP','PhaseTransition','CompositionAtPhaseEquilibrium','ActivityFugacityOsmoticProp','VolumetricProp','HeatCapacityAndDerivedProp','ExcessPartialApparentEnergyProp','TransportProp','RefractionSurfaceTensionSoundSpeed','BioProperties') COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'XML schema group (enum)',
  `vartype` enum('eTemperature','ePressure','eComponentComposition','eSolventComposition','eMiscellaneous','eBioVariables','eParticipantAmount') COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'quantity type (enum)',
  `symbol` varchar(64) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'symbol',
  `definition` varchar(1024) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'definition of quantity',
  `source` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'source of definition',
  `kind` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'kind of quantity',
  `quantitykind_id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {quantitykinds}',
  `defunit_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {units}',
  `condcnt` mediumint(8) UNSIGNED DEFAULT NULL COMMENT 'count of conditions of this quantity',
  `datacnt` mediumint(8) UNSIGNED DEFAULT NULL COMMENT 'count of datums of this quantity',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of chemical properties';

-- --------------------------------------------------------

--
-- Table structure for table `quantitykinds`
--

CREATE TABLE `quantitykinds` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'name',
  `altname` varchar(64) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'alternate name',
  `symbol` varchar(64) COLLATE utf8mb4_bin NOT NULL COMMENT 'symbol',
  `definition` varchar(1024) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'definition of quantity kind',
  `source` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'source of definition',
  `si_unit` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {units}',
  `dim_symbol` varchar(64) COLLATE utf8mb4_bin NOT NULL COMMENT 'dimensionality symbol',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of quantities';

-- --------------------------------------------------------

--
-- Table structure for table `references`
--

CREATE TABLE `references` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `journal_id` tinyint(3) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {journals}',
  `authors` varchar(2048) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'list of authors as JSON',
  `aulist` varchar(2048) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'semicolon separated list of authors',
  `year` smallint(4) UNSIGNED DEFAULT NULL COMMENT 'year of publication',
  `volume` varchar(12) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'volume',
  `issue` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'issue',
  `startpage` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'first page',
  `endpage` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'last page',
  `title` varchar(1024) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'title',
  `doi` varchar(256) COLLATE utf8mb4_bin NOT NULL COMMENT 'digital object identifer',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='References from Springer Materials PDFs';

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `title` varchar(512) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'title (autogenerated)',
  `description` varchar(1024) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'description (autogenerated)',
  `file_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {files}',
  `reference_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {references}',
  `points` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'number of points in report',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Reports of data from the literature' ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `sampleprops`
--

CREATE TABLE `sampleprops` (
  `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `dataset_id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {datasets}',
  `propnum` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'property number in XML',
  `orgnum` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'substance number in XML',
  `quantity_group` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'group from XML',
  `quantity_name` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'name of quantity',
  `quantity_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {quantities}',
  `unit_id` smallint(5) UNSIGNED ZEROFILL DEFAULT NULL COMMENT 'foreign key {units}',
  `method_name` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'method name',
  `phase` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'phase of sample',
  `presentation` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'how data was presented from XML',
  `solventorgnum` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT '<orgnum> of solvent',
  `uncnum` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'uncertainty number from XML',
  `unceval` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'uncertainty evaluation from XML',
  `uncconf` varchar(8) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'uncertainty confidence level',
  `uncchk` enum('yes','no') COLLATE utf8mb4_bin NOT NULL DEFAULT 'no' COMMENT 'uncertainty check (enum)',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `substances`
--

CREATE TABLE `substances` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(1024) COLLATE utf8mb4_bin NOT NULL COMMENT 'name',
  `type` enum('element','compound','not found') COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'type of susbtance (enum)',
  `subtype` varchar(256) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'subtype of susbtance (enum)',
  `formula` varchar(256) COLLATE utf8mb4_bin NOT NULL COMMENT 'molecular formula',
  `mw` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'molecular weight',
  `mwsrc` varchar(16) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'source of molecular weight',
  `inchikey` varchar(27) COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'IUPAC InChIKey',
  `files` smallint(4) UNSIGNED DEFAULT NULL COMMENT 'number of files substance occurs in',
  `systems` smallint(4) UNSIGNED DEFAULT NULL COMMENT 'number of systems substance is part of',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of compounds from Springer materials';

-- --------------------------------------------------------

--
-- Table structure for table `substances_systems`
--

CREATE TABLE `substances_systems` (
  `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `substance_id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {substances}',
  `system_id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'foreign key {systems}',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `systems`
--

CREATE TABLE `systems` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(300) COLLATE utf8mb4_bin NOT NULL COMMENT 'name (autogenerated)',
  `composition` enum('pure substance','binary mixture','ternary mixture','quaternary mixture','quinternary mixture','pure compound') COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'composition, pure or mixture type',
  `identifier` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'unique id from system based on substance ids',
  `refcnt` smallint(3) UNSIGNED DEFAULT NULL COMMENT 'number of references system is found in',
  `setcnt` smallint(4) UNSIGNED DEFAULT NULL COMMENT 'number of datasets with this system',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` smallint(5) UNSIGNED ZEROFILL NOT NULL COMMENT 'primary key',
  `name` varchar(256) COLLATE utf8mb4_bin NOT NULL COMMENT 'name',
  `symbol` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'symbol',
  `type` enum('si','siderived','cgs','imperial','inversesi','other') COLLATE utf8mb4_bin NOT NULL COMMENT 'type of unit (enum)',
  `exact` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'is factor exact?',
  `factor` float NOT NULL DEFAULT '1' COMMENT 'conversion factor to SI',
  `si_equivalent` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'equivalent in the SI',
  `qudt` varchar(128) COLLATE utf8mb4_bin NOT NULL COMMENT 'QUDT unit encoding',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of scientific units';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` tinyint(3) UNSIGNED ZEROFILL NOT NULL,
  `username` varchar(16) COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(1024) COLLATE utf8mb4_bin NOT NULL,
  `firstname` varchar(16) COLLATE utf8mb4_bin NOT NULL,
  `lastname` varchar(16) COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(32) COLLATE utf8mb4_bin DEFAULT NULL,
  `type` enum('regular','admin','superadmin') COLLATE utf8mb4_bin NOT NULL DEFAULT 'regular',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='Table of users';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chemicals`
--
ALTER TABLE `chemicals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `substances` (`substance_id`) USING BTREE,
  ADD KEY `files` (`file_id`) USING BTREE;

--
-- Indexes for table `chemicals_datasets`
--
ALTER TABLE `chemicals_datasets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chemicals` (`chemical_id`) USING BTREE,
  ADD KEY `datasets` (`dataset_id`) USING BTREE;

--
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chemicals` (`chemical_id`) USING BTREE,
  ADD KEY `mixtures` (`mixture_id`) USING BTREE;

--
-- Indexes for table `conditions`
--
ALTER TABLE `conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conds_comps` (`component_id`),
  ADD KEY `fk_conds_phases` (`phase_id`),
  ADD KEY `fk_conds_points` (`datapoint_id`),
  ADD KEY `fk_conds_quants` (`quantity_id`),
  ADD KEY `fk_conds_series` (`dataseries_id`),
  ADD KEY `fk_conds_sets` (`dataset_id`),
  ADD KEY `fk_conds_syss` (`system_id`),
  ADD KEY `fk_conds_units` (`unit_id`);

--
-- Indexes for table `crosswalks`
--
ALTER TABLE `crosswalks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `components` (`component_id`) USING BTREE,
  ADD KEY `quantities` (`quantity_id`) USING BTREE,
  ADD KEY `dataseries` (`dataseries_id`) USING BTREE,
  ADD KEY `phases` (`phase_id`) USING BTREE,
  ADD KEY `datasets` (`dataset_id`) USING BTREE,
  ADD KEY `sampleprops` (`sampleprop_id`) USING BTREE,
  ADD KEY `units` (`unit_id`) USING BTREE,
  ADD KEY `datapoints` (`datapoint_id`) USING BTREE;

--
-- Indexes for table `datapoints`
--
ALTER TABLE `datapoints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataseries` (`dataseries_id`) USING BTREE,
  ADD KEY `datasets` (`dataset_id`) USING BTREE;

--
-- Indexes for table `dataseries`
--
ALTER TABLE `dataseries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `datasets` (`dataset_id`) USING BTREE;

--
-- Indexes for table `datasets`
--
ALTER TABLE `datasets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `files` (`file_id`) USING BTREE,
  ADD KEY `reports` (`report_id`) USING BTREE,
  ADD KEY `references` (`reference_id`) USING BTREE,
  ADD KEY `systems` (`system_id`) USING BTREE;

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `year` (`year`),
  ADD KEY `references` (`reference_id`) USING BTREE;

--
-- Indexes for table `identifiers`
--
ALTER TABLE `identifiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `substances` (`substance_id`) USING BTREE;

--
-- Indexes for table `journals`
--
ALTER TABLE `journals`
  ADD UNIQUE KEY `ID` (`id`);

--
-- Indexes for table `keywords`
--
ALTER TABLE `keywords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_keys_reports` (`report_id`);

--
-- Indexes for table `mixtures`
--
ALTER TABLE `mixtures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys-set` (`system_id`,`dataset_id`),
  ADD KEY `datasets` (`dataset_id`) USING BTREE;

--
-- Indexes for table `nspaces`
--
ALTER TABLE `nspaces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `path` (`path`);

--
-- Indexes for table `ontterms`
--
ALTER TABLE `ontterms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phases`
--
ALTER TABLE `phases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mixes` (`mixture_id`) USING BTREE,
  ADD KEY `ptypes` (`phasetype_id`) USING BTREE;

--
-- Indexes for table `phasetypes`
--
ALTER TABLE `phasetypes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purificationsteps`
--
ALTER TABLE `purificationsteps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_steps_chems` (`chemical_id`),
  ADD KEY `fk_steps_units` (`purityunit_id`);

--
-- Indexes for table `quantities`
--
ALTER TABLE `quantities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quants_qkinds` (`quantitykind_id`),
  ADD KEY `fk_quants_units` (`defunit_id`);

--
-- Indexes for table `quantitykinds`
--
ALTER TABLE `quantitykinds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `units` (`si_unit`) USING BTREE;

--
-- Indexes for table `references`
--
ALTER TABLE `references`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_refs_jrnls` (`journal_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_refs_reports` (`reference_id`),
  ADD KEY `fk_files_reports` (`file_id`);

--
-- Indexes for table `sampleprops`
--
ALTER TABLE `sampleprops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataset_id` (`dataset_id`),
  ADD KEY `units` (`unit_id`) USING BTREE,
  ADD KEY `fk_sprops_quants` (`quantity_id`);

--
-- Indexes for table `substances`
--
ALTER TABLE `substances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `substances_systems`
--
ALTER TABLE `substances_systems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `systems` (`system_id`) USING BTREE,
  ADD KEY `substances` (`substance_id`) USING BTREE;

--
-- Indexes for table `systems`
--
ALTER TABLE `systems`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identifier` (`identifier`),
  ADD KEY `name` (`name`),
  ADD KEY `composition` (`composition`) USING BTREE;

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chemicals`
--
ALTER TABLE `chemicals`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chemicals_datasets`
--
ALTER TABLE `chemicals_datasets`
  MODIFY `id` mediumint(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'foreign key';

--
-- AUTO_INCREMENT for table `conditions`
--
ALTER TABLE `conditions`
  MODIFY `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `crosswalks`
--
ALTER TABLE `crosswalks`
  MODIFY `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data`
--
ALTER TABLE `data`
  MODIFY `id` mediumint(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `datapoints`
--
ALTER TABLE `datapoints`
  MODIFY `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `dataseries`
--
ALTER TABLE `dataseries`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `datasets`
--
ALTER TABLE `datasets`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `identifiers`
--
ALTER TABLE `identifiers`
  MODIFY `id` mediumint(8) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `journals`
--
ALTER TABLE `journals`
  MODIFY `id` tinyint(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `keywords`
--
ALTER TABLE `keywords`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `mixtures`
--
ALTER TABLE `mixtures`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `nspaces`
--
ALTER TABLE `nspaces`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ontterms`
--
ALTER TABLE `ontterms`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phases`
--
ALTER TABLE `phases`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `phasetypes`
--
ALTER TABLE `phasetypes`
  MODIFY `id` tinyint(2) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `purificationsteps`
--
ALTER TABLE `purificationsteps`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `quantities`
--
ALTER TABLE `quantities`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `quantitykinds`
--
ALTER TABLE `quantitykinds`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `sampleprops`
--
ALTER TABLE `sampleprops`
  MODIFY `id` mediumint(7) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `substances`
--
ALTER TABLE `substances`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `substances_systems`
--
ALTER TABLE `substances_systems`
  MODIFY `id` mediumint(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `systems`
--
ALTER TABLE `systems`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` smallint(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT COMMENT 'primary key';

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` tinyint(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chemicals`
--
ALTER TABLE `chemicals`
  ADD CONSTRAINT `fk_chems_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`),
  ADD CONSTRAINT `fk_chems_subs` FOREIGN KEY (`substance_id`) REFERENCES `substances` (`id`);

--
-- Constraints for table `chemicals_datasets`
--
ALTER TABLE `chemicals_datasets`
  ADD CONSTRAINT `fk_chemsets_chems` FOREIGN KEY (`chemical_id`) REFERENCES `chemicals` (`id`),
  ADD CONSTRAINT `fk_chemsets_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`);

--
-- Constraints for table `components`
--
ALTER TABLE `components`
  ADD CONSTRAINT `fk_comps_chems` FOREIGN KEY (`chemical_id`) REFERENCES `chemicals` (`id`),
  ADD CONSTRAINT `fk_comps_mixes` FOREIGN KEY (`mixture_id`) REFERENCES `mixtures` (`id`);

--
-- Constraints for table `conditions`
--
ALTER TABLE `conditions`
  ADD CONSTRAINT `fk_conds_comps` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`),
  ADD CONSTRAINT `fk_conds_phases` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`),
  ADD CONSTRAINT `fk_conds_points` FOREIGN KEY (`datapoint_id`) REFERENCES `datapoints` (`id`),
  ADD CONSTRAINT `fk_conds_quants` FOREIGN KEY (`quantity_id`) REFERENCES `quantities` (`id`),
  ADD CONSTRAINT `fk_conds_series` FOREIGN KEY (`dataseries_id`) REFERENCES `dataseries` (`id`),
  ADD CONSTRAINT `fk_conds_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`),
  ADD CONSTRAINT `fk_conds_syss` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`),
  ADD CONSTRAINT `fk_conds_units` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `data`
--
ALTER TABLE `data`
  ADD CONSTRAINT `fk_data_comps` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`),
  ADD CONSTRAINT `fk_data_phases` FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`),
  ADD CONSTRAINT `fk_data_points` FOREIGN KEY (`datapoint_id`) REFERENCES `datapoints` (`id`),
  ADD CONSTRAINT `fk_data_quants` FOREIGN KEY (`quantity_id`) REFERENCES `quantities` (`id`),
  ADD CONSTRAINT `fk_data_sers` FOREIGN KEY (`dataseries_id`) REFERENCES `dataseries` (`id`),
  ADD CONSTRAINT `fk_data_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`),
  ADD CONSTRAINT `fk_data_sprops` FOREIGN KEY (`sampleprop_id`) REFERENCES `sampleprops` (`id`),
  ADD CONSTRAINT `fk_data_units` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `datapoints`
--
ALTER TABLE `datapoints`
  ADD CONSTRAINT `fk_points_sers` FOREIGN KEY (`dataseries_id`) REFERENCES `dataseries` (`id`),
  ADD CONSTRAINT `fk_points_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`);

--
-- Constraints for table `dataseries`
--
ALTER TABLE `dataseries`
  ADD CONSTRAINT `fk_sers_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`);

--
-- Constraints for table `datasets`
--
ALTER TABLE `datasets`
  ADD CONSTRAINT `fk_datasets_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`),
  ADD CONSTRAINT `fk_datasets_refs` FOREIGN KEY (`reference_id`) REFERENCES `references` (`id`),
  ADD CONSTRAINT `fk_datasets_reps` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`),
  ADD CONSTRAINT `fk_datasets_syss` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`);

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_refs` FOREIGN KEY (`reference_id`) REFERENCES `references` (`id`);

--
-- Constraints for table `identifiers`
--
ALTER TABLE `identifiers`
  ADD CONSTRAINT `fk_idents_subs` FOREIGN KEY (`substance_id`) REFERENCES `substances` (`id`);

--
-- Constraints for table `keywords`
--
ALTER TABLE `keywords`
  ADD CONSTRAINT `fk_keys_reports` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`);

--
-- Constraints for table `mixtures`
--
ALTER TABLE `mixtures`
  ADD CONSTRAINT `fk_mixes_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`),
  ADD CONSTRAINT `fk_mixes_syss` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`);

--
-- Constraints for table `phases`
--
ALTER TABLE `phases`
  ADD CONSTRAINT `fk_phases_mixes` FOREIGN KEY (`mixture_id`) REFERENCES `mixtures` (`id`),
  ADD CONSTRAINT `fk_phases_ptypes` FOREIGN KEY (`phasetype_id`) REFERENCES `phasetypes` (`id`);

--
-- Constraints for table `purificationsteps`
--
ALTER TABLE `purificationsteps`
  ADD CONSTRAINT `fk_steps_chems` FOREIGN KEY (`chemical_id`) REFERENCES `chemicals` (`id`),
  ADD CONSTRAINT `fk_steps_units` FOREIGN KEY (`purityunit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `quantities`
--
ALTER TABLE `quantities`
  ADD CONSTRAINT `fk_quants_qkinds` FOREIGN KEY (`quantitykind_id`) REFERENCES `quantitykinds` (`id`),
  ADD CONSTRAINT `fk_quants_units` FOREIGN KEY (`defunit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `quantitykinds`
--
ALTER TABLE `quantitykinds`
  ADD CONSTRAINT `fk_qkinds_units` FOREIGN KEY (`si_unit`) REFERENCES `units` (`id`);

--
-- Constraints for table `references`
--
ALTER TABLE `references`
  ADD CONSTRAINT `fk_refs_jrnls` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`),
  ADD CONSTRAINT `fk_reports_refs` FOREIGN KEY (`reference_id`) REFERENCES `references` (`id`);

--
-- Constraints for table `sampleprops`
--
ALTER TABLE `sampleprops`
  ADD CONSTRAINT `fk_sprops_quants` FOREIGN KEY (`quantity_id`) REFERENCES `quantities` (`id`),
  ADD CONSTRAINT `fk_sprops_sets` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`),
  ADD CONSTRAINT `fk_sprops_units` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `substances_systems`
--
ALTER TABLE `substances_systems`
  ADD CONSTRAINT `fk_subsys_subs` FOREIGN KEY (`substance_id`) REFERENCES `substances` (`id`),
  ADD CONSTRAINT `fk_subsys_syss` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
