<?php
namespace mirkhamidov\treemanager\assets;


use yii\bootstrap\BootstrapAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class Assets extends AssetBundle
{
    public $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'public';

    public $css = [
        'css/style.css',
    ];

    public $js = [
        'js/jquery.noty.packaged.min.js',
        'js/jquery.noty.theme.js',
        'js/script.js',
    ];

    public $depends = [
        JqueryAsset::class,
        BootstrapAsset::class,
        \rmrevin\yii\fontawesome\AssetBundle::class,
    ];
}