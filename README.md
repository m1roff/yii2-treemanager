yii2-treemanager
=

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
composer require mirkhamidov/yii2-treemanager "*"
```

or add

```
"mirkhamidov/yii2-treemanager": "*"
```

to the require section of your `composer.json` file.


Usage
-----

```php
echo mirkhamidov\treemanager\TreeManagerWidget::widget([
    'rootNodeId' => $rootId,
    'modelClass' => $modelClass,
]);
```