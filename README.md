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
          "property": "[%locale%][body]"
        },
        {
          "selector": "h1",
          "property": "[%locale%][title]"
        }
      ]
    }
  ]
}
```