<?php

namespace mirkhamidov\treemanager;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\Request;
use yii\web\Response;
use yii\web\Session;
use yii\web\UrlManager;

/**
 * Class TreeManagerWidget
 *
 * @property ActiveRecord[] $data
 * @property ActiveRecord $selectedItem
 * @property ActiveRecord $currentItem
 * @property ActiveRecord $currentModel
 * @property ActiveRecord $rootNode
 * @property ActiveQuery $safeQuery
 * @property ActiveQuery $query
 * @property string $currentRoute
 * @property string $action
 * @property string $modelClass
 * @property string $pjaxId
 */
class TreeManagerWidget extends Widget
{

    /**
     * @var string Для вывода заголовка
     */
    public $title = 'Категории';

    /**
     * @var string Для вывода Lable
     */
    public $itemAttribute = 'name';

    /**
     * @var string
     */
    public $levelAttribute = 'lvl';

    /**
     * @var string Для передачи PK
     */
    public $selectedParamName = 'treeeid';

    /**
     * @var string Для передачи действий
     */
    public $actionParamName = 'treaction';

    public $force = false;

    /** @var  integer */
    public $rootNodeId;

    /** @var  string */
    public $modelClass;

    /** @var  Request */
    private $request;

    /** @var  UrlManager */
    private $urlManager;

    /** @var  Response */
    private $response;

    /** @var  Session */
    private $session;

    /** @var null|string */
    private $sModelClass = null;
    private $sCurrentModel = null;

    /** @var  ActiveRecord[] */
    private $_data;

    /** @var  boolean */
    private $_forceReload = false;

    /**
     * @var null|string
     */
    private $sRedirect = null;

    const ACTION_CREATE = 'newroot';
    const ACTION_DELETE = 'delete';
    const ACTION_UP = 'up';
    const ACTION_DOWN = 'down';
    const ACTION_CHILD = 'child';

    const SUCCESS_FLASH = '_tree_success';
    const FAIL_FLASH = '_tree_fail';

    const PJAX_ID_PREFIX = 'pjax-tree-manager-';

    /** @inheritdoc */
    public function init()
    {
        if (empty($this->modelClass) || !is_string($this->modelClass)) {
            throw new InvalidConfigException('Укажите параметр $modelClass');
        }
        if (empty($this->rootNodeId) || !is_integer($this->rootNodeId)) {
            throw new InvalidConfigException('Укажите параметр $rootNodeId');
        }

        $this->request = \Yii::$app->request;
        $this->urlManager = \Yii::$app->urlManager;
        $this->response = \Yii::$app->response;
        $this->session = \Yii::$app->session;


        // Сохранение данных
        if ($this->request->isPjax && !$this->action && ($id = $this->request->get($this->selectedParamName, false))) {
            $model = $this->currentModel->findOne($id);
            if ($model->load($this->request->post()) && $model->save()) {
                $this->session->setFlash(self::SUCCESS_FLASH, 'Данные сохранены');
            }
        }
        // END Сохранение данных

        // Манипуляции деревом
        if ($this->request->isPjax && $this->action) {
            $selectedId = $this->request->get($this->selectedParamName, false);
            switch ($this->action) {
                case self::ACTION_DOWN:
                    $sSibling = 'next';
                    $sSiblingMethod = 'insertAfter';
                case self::ACTION_UP:
                    if (empty($sSibling)) {
                        $sSibling = 'prev';
                    }
                    if (empty($sSiblingMethod)) {
                        $sSiblingMethod = 'insertBefore';
                    }
                    $model = $this->currentModel->findOne($selectedId);
                    $siblingQuery = $model->{$sSibling}();
                    if ($siblingQuery->exists()) {
                        $siblingModel = $siblingQuery->one();
                        if ($model->{$sSiblingMethod}($siblingModel)) {
                            $this->session->setFlash(self::SUCCESS_FLASH,
                                Yii::t('app', 'Элемент "{name}" перемещен.', ['name' => $model->name]));
                            return $this->response->redirect($this->getRedirectRouteToIndex());
                        }
                    } else {
                        $this->session->setFlash(self::FAIL_FLASH,
                            Yii::t('app', 'Элемент "{name}" некуда перемещать.', ['name' => $model->name]));
                    }
                    break;

                case self::ACTION_DELETE:
                    $model = $this->currentModel->findOne($selectedId);
                    if ($model->delete()) {
                        $this->session->setFlash(self::SUCCESS_FLASH, Yii::t('app', 'Элемент "{name}" удален.', ['name' => $model->name]));
                        return $this->response->redirect($this->getRedirectRouteToIndex());
                    }
                    break;

                case self::ACTION_CHILD:
                    $appendToModel = $this->currentModel->findOne($selectedId);
                    $model = $this->currentModel;
                    if ($model->load($this->request->post())) {
                        if ($model->appendTo($appendToModel)) {
                            $this->session->setFlash(self::SUCCESS_FLASH, 'Дочерний элемент создан.');
                            return $this->response->redirect($this->getRedirectRouteToItem($model));
                        }
                    }

                    break;

                case self::ACTION_CREATE:
                    $model = $this->currentModel;
                    if ($selectedId) {
                        $model = $this->currentModel->findOne($selectedId);
                    }
                    if ($model->load($this->request->post())) {
                        $sLvl = $this->safeQuery->select('lvl')->scalar();
                        if ($sLvl === false ) {
                            if ($model->appendTo($this->rootNode)) {
                                $this->session->setFlash(self::SUCCESS_FLASH, 'Корневой элемент создан.1');
                                return $this->response->redirect($this->getRedirectRouteToItem($model));
                            }
                        } elseif ($sLvl == 0 && $model->makeRoot()) {
                            $this->session->setFlash(self::SUCCESS_FLASH, 'Корневой элемент создан.2');
                            return $this->response->redirect($this->getRedirectRouteToItem($model));
                        } else {
                            $q = $this->safeQuery;
                            $q->orderBy([
                                'root' => SORT_DESC,
                                'lvl' => SORT_ASC,
                                'lft' => SORT_DESC,
                            ]);
                            $afterModel = $q->one();
                            if ($model->insertAfter($afterModel)) {
                                $this->session->setFlash(self::SUCCESS_FLASH, 'Корневой элемент создан.3');
                                $this->_forceReload = true;
                                return $this->response->redirect($this->getRedirectRouteToItem($model));
                            }
                        }
                    }
                    break;
            }
        }

        parent::init();
    }

    /** @inheritdoc */
    public function run()
    {
        return $this->render('view', [
            'session' => $this->session,
        ]);
    }

    /**
     * @return string
     */
    public function getModelClass()
    {
        if ($this->sModelClass === null) {
            $this->sModelClass = $this->query->modelClass;
        }
        return $this->sModelClass;
    }

    /**
     * Setter
     * @param $v
     */
    public function setModelClass($v)
    {
        $this->modelClass = $v;
    }

    /**
     * Getter
     * @return string
     */
    public function getCurrentRoute()
    {
        return \Yii::$app->controller->getRoute();
    }

    /**
     * @return bool
     */
    public function getForceReload()
    {
        return $this->_forceReload;
    }

    /**
     * @return string
     */
    public function getPjaxId()
    {
        return self::PJAX_ID_PREFIX . $this->id;
    }

    /**
     * @return \yii\db\ActiveRecord[]
     */
    public function getData()
    {
        if (!$this->_data && $this->force === false) {
                $this->_data = $this->safeQuery->all();
        }
        return $this->_data;
    }

    /**
     * Получить значение действия из роута
     * @return string|null
     */
    public function getAction()
    {
        return $this->request->getQueryParam($this->actionParamName, null);
    }

    /**
     * @return ActiveRecord
     */
    public function getRootNode()
    {
        return (Yii::createObject($this->modelClass))::findOne($this->rootNodeId);
    }

    /**
     * @return ActiveQuery
     */
    public function getQuery()
    {
        return $this->rootNode->children();
    }

    /**
     * @return ActiveQuery
     */
    public function getSafeQuery()
    {
        return clone $this->query;
    }

    /**
     * @return null|ActiveRecord
     */
    public function getCurrentModel()
    {
        if ($this->sCurrentModel === null) {
            $this->sCurrentModel = \Yii::createObject($this->modelClass);
        }
        return $this->sCurrentModel;
    }

    /**
     * Setter
     * @param $v
     */
    public function setCurrentModel($v)
    {
        $this->sCurrentModel = $v;
    }

    /**
     * @return null|ActiveRecord
     */
    public function getCurrentItem()
    {
        if ($this->action) {
            switch ($this->action) {
                // Новая запись к текущему корневому
                case self::ACTION_CREATE:
                    return $this->currentModel;
                    break;

                case self::ACTION_CHILD:
                    return $this->currentModel;
                    break;

                default:
                    return null;
                    break;
            }
        } else {
            return $this->getSelectedItem();
        }
    }

    /**
     * @return null|ActiveRecord
     */
    public function getSelectedItem()
    {
        if (($selectedId = $this->request->get($this->selectedParamName, false))) {
            return $this->safeQuery->andWhere(['id' => $selectedId])->one();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getItemCancelLinkRoute()
    {
        $params = [$this->currentRoute];
        return $this->urlManager->createUrl($params);
    }

    /**
     * @return null|string
     */
    public function getNeedRedirect()
    {
        return $this->sRedirect;
    }

    /**
     * @return null|string
     */
    public function getRedirectRouteToIndex()
    {
        $params = [$this->currentRoute];
        $this->sRedirect = $this->urlManager->createUrl($params);
        return $this->sRedirect;
    }

    /**
     * @param $item
     * @return null|string
     */
    public function getRedirectRouteToItem($item)
    {
        $params = [$this->currentRoute];
        $params[$this->selectedParamName] = $item->id;
        $this->sRedirect = $this->urlManager->createUrl($params);
        return $this->sRedirect;
    }

    /**
     * @return string
     */
    public function getItemNewLinkRoute()
    {
        $params = [$this->currentRoute];
        $params[$this->actionParamName] = self::ACTION_CREATE;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @param ActiveRecord $item
     * @return string
     */
    public function getItemEditLinkRoute(ActiveRecord $item)
    {
        $params = [$this->currentRoute];
        $params[$this->selectedParamName] = $item->id;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @param ActiveRecord $item
     * @return string
     */
    public function getItemDeleteLinkRoute(ActiveRecord $item)
    {
        $params = [$this->currentRoute];
        $params[$this->actionParamName] = self::ACTION_DELETE;
        $params[$this->selectedParamName] = $item->id;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @param ActiveRecord $item
     * @return string
     */
    public function getItemUpLinkRoute(ActiveRecord $item)
    {
        $params = [$this->currentRoute];
        $params[$this->actionParamName] = self::ACTION_UP;
        $params[$this->selectedParamName] = $item->id;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @param ActiveRecord $item
     * @return string
     */
    public function getItemDownLinkRoute(ActiveRecord $item)
    {
        $params = [$this->currentRoute];
        $params[$this->actionParamName] = self::ACTION_DOWN;
        $params[$this->selectedParamName] = $item->id;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @param ActiveRecord $item
     * @return string
     */
    public function getItemChildLinkRoute(ActiveRecord $item)
    {
        $params = [$this->currentRoute];
        $params[$this->actionParamName] = self::ACTION_CHILD;
        $params[$this->selectedParamName] = $item->id;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @return string
     */
    public function getCurrentUrl()
    {
        $params = ArrayHelper::filter($this->request->queryParams, [$this->actionParamName, $this->selectedParamName]);
        $params[0] = $this->currentRoute;
        return $this->urlManager->createUrl($params);
    }

    /**
     * @param ActiveRecord $item
     * @return string
     */
    public function getItemLinkLabel(ActiveRecord $item)
    {
        return $this->getIdent($item->{$this->levelAttribute}) . $item->{$this->itemAttribute};
    }

    /**
     * @param $count
     * @return null|string
     */
    public function getIdent($count)
    {
        if ($count == 1) {
            return null;
        } else {
            $count *= 5;
        }
        return str_repeat('&nbsp;', $count);
    }

    /**
     * @param ActiveRecord $item
     * @return bool
     */
    public function getIsItemActive(ActiveRecord $item)
    {
        $r = $this->request->get($this->selectedParamName, false);
        if ($r && $r == $item->id) {
            return true;
        }
        return false;
    }
}