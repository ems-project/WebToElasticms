# WebToElasticms

With this Symfony single command, you can update elasticms documents by tracking web resources.

Usage 
 - `php application.php https://my-elasticms.com /path/to/a/json/config/file.json`

If you are not using a Linux environment, we suggest you to use a PHP docker image. I.e. under Windows with Docker Desktop: 

`docker run -it -v %cd%:/opt/src -w /opt/src elasticms/base-php-dev:7.4 php -d memory_limit=-1 application.php  https://my-elasticms.com /opt/src/config.json --cache-folder=/opt/src/cache`

The JSON config file list all web resources to synchronise for each document.

```json
{
  "documents": [
    {
      "resources": [
        {
          "url": "https://fqdn.com/fr/page",
          "locale": "fr",
          "type": "infopage"
        },
        {
          "url": "https://fqdn.com/nl/page",
          "locale": "nl",
          "type": "infopage"
        }
      ]
    }
  ],
  "analyzers": [
    {
      "name": "infopage",
      "type": "html",
      "extractors": [
        {
          "selector": "div.field-name-body div.field-item",
          "property": "[%locale%][body]",
          "filters": [
            "internal-link",
            "style-cleaner",
            "class-cleaner"
          ]
        },
        {
          "selector": "h1",
          "property": "[%locale%][title]",
          "filters": [
            "striptags"
          ]
        }
      ]
    }
  ],
  "validClasses": ["toc"],
  "linkToClean": ["/^\\/fr\\/glossaire/"],
  "urlsNotFound": [
    "\/fr\/page-not-found"
  ],
  "linksByUrl": {
    "\/": "ems:\/\/object:page:xaO1YHoBFgLgfwq-PbIl"
  },
  "documentsToClean": {
    "page": [
      "w9WS4X0BFgLgfwq-9hDd",
      "y9YG4X0BeD9wLAROUfIV"
    ]
  }
}
```

## Filters

### class-cleaner

This filter remove all html class but the ones defined in the top level `validClasses` attribute. 

### internal-link

This filter convert internal links. A link is considered as an internal link if the link is relative, absolute or share the host with at least one resource. Internal link are converted following the ordered rules :
 - Link with a path matching at least on regex defined in the top level `linkToClean` attribute.
 - Link where the path match one of the resource with be converted to an ems link to document containing the resource
 - Link to an asset that is not a text/html are converte to an ems link to the asset (and the asset is uplaoded)

### style-cleaner

This filter remove all style attribute. 


### striptags

This filter extract the text and remove all the rest

## Types

### tempFields

Array of string used to remove field from the data in order to not sent them to elasticms. It may append that you used temporary fields in order to save extractor values and used those values in computers. 

### Computer

#### Expression

Those parameters are using the [Symfony expression syntax](https://symfony.com/doc/current/components/expression_language/syntax.html)

Functions available: 
 - `uuid()`: generate a unique identifier
 - `json_escape(str)`: JSON escape a string 
 - `date(format, timestamp)`: Format a date 
 - `strtotime(str)`: Convert a string into a date 

Variable available
 - `data` an instance of [ExpressionData](src/App/Helper/ExpressionData.php)

## Docker

### Build 

```
docker build -t docker.io/elasticms/web2ems:latest .
```

### Running

```
docker run --rm -v <LOCAL_FILE_PATH>:<CONTAINER_FILE_PATH> docker.io/elasticms/web2ems:latest -f application.php <URL> <CONTAINER_FILE_PATH>
```