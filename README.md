[![DOI](https://zenodo.org/badge/85196514.svg)](https://zenodo.org/badge/latestdoi/85196514)

## CakePHP Website to Process NIST TRC ThermoML Data
This CakePHP website was created to ingest thermophysical property data in the IUPAC ThermoML XML format from
[https://trc.nist.gov/ThermoML.html](https://trc.nist.gov/ThermoML.html) into a relational database (MySQL).
The intent was to create a REST website where the data could be searched, viewed and download in other formats,
including SciData JSON-LD.

Subsequently, the code was updated to convert the script that created the SciData JSON-LD to use a library class
(a model file in CakePHP) that could be used for other projects.

Information about aspects of the code can be found in each of the folders.  Additionally, a paper will be published
on this code outlining the overall process and the datasets produced.  The datasets can be found at the following
locations (papers in the works):

- [Dataset-NIST-TRC-MySQL](https://github.com/ChalkLab/Dataset-NIST-TRC-MySQL)
- [Dataset-NIST-TRC-JSONLD](https://github.com/ChalkLab/Dataset-NIST-TRC-JSONLD)

In addition, the intention is to develop two new versions of this repo based on updating the code

- A CakePHP v4 version (this site is built with CakePHP v2 which is now end of life)
- A Python/Django version
