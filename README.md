# WebToElasticms

With this Symfony single command, you can update elasticms documents by tracking web resources.

Usage 
 - `php application.php https://my-elasticms.com /path/to/a/json/config/file.json`

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
  "linkToClean": ["/^\\/fr\\/glossaire/"]
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

Variable available
 - `data` an instance of [ExpressionData](src/App/Helper/ExpressionData.php)

