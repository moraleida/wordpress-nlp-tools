# Natural Language Processing Tools for WordPress
A toolkit for using self-hosted Natural Language Processing in WordPress

### This plugin is a Proof of Concept and not ready for production

## Requirements:
- Elasticsearch 6.2.2+ running in a server you can install the models to
- ElasticPress

## Installation
1. Follow the installation steps for the [Elasticsearch OpenNLP Ingest Processor](https://github.com/spinscale/elasticsearch-ingest-opennlp): install a processor version matching your Elasticsearch version, and don't forget to download the built-in modules. E.g:

```
$ bin/elasticsearch-plugin install https://github.com/spinscale/elasticsearch-ingest-opennlp/releases/download/7.13.3.1/ingest-opennlp-7.13.3.1.zip
$ bin/ingest-opennlp/download-models
```

Configure `elasticsearch.yml` to read the modules:
```
ingest.opennlp.model.file.persons: en-ner-persons.bin
ingest.opennlp.model.file.dates: en-ner-dates.bin
ingest.opennlp.model.file.locations: en-ner-locations.bin
```

2. Install and activate this plugin
3. Add the following code to your `functions.php` to map the entities extracted to any existing taxonomies (optional):
