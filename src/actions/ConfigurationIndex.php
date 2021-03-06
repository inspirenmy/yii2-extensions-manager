<?php

namespace DevGroup\ExtensionsManager\actions;

use DevGroup\AdminUtils\actions\TabbedFormCombinedAction;
use DevGroup\ExtensionsManager\ExtensionsManager;
use DevGroup\ExtensionsManager\helpers\ExtensionsHelper;
use DevGroup\ExtensionsManager\models\BaseConfigurationModel;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\web\ForbiddenHttpException;

/**
 * Class ConfigurationIndex
 *
 * @package DevGroup\ExtensionsManager\actions
 */
class ConfigurationIndex extends TabbedFormCombinedAction
{
    /** @var BaseConfigurationModel */
    public $model = null;

    /** @var array All configurables array */
    public $configurables = [];

    /** @var array Current selected configurable */
    public $currentConfigurable = [];

    /** @var string Name of configuration model */
    public $currentConfigurationModel = null;

    /** @var string Configuration view */
    public $currentConfigurationView = '';

    /** @var int Current section index in $configurables array */
    public $sectionIndex = 0;

    /** @var bool If current selected section is valid */
    public $isValidSection = false;

    /**
     * @inheritdoc
     */
    public function beforeActionRun()
    {
        parent::beforeActionRun();

        $this->configurables = ExtensionsHelper::getConfigurables(true);
        $this->sectionIndex = Yii::$app->request->get('sectionIndex', 0);
        if (isset($this->configurables[$this->sectionIndex]) === false) {
            $this->sectionIndex = 0;
        }
        $this->sectionIndex = intval($this->sectionIndex);

        $this->currentConfigurable = isset($this->configurables[$this->sectionIndex])
            ? $this->configurables[$this->sectionIndex]
            : null;
        if ($this->currentConfigurable !== null
            && ExtensionsManager::module()->extensionIsCore($this->currentConfigurable['package'])
            && !Yii::$app->user->can('extensions-manager-access-to-core-extension')
        ) {
            throw new ForbiddenHttpException;
        }
        if (null !== $this->currentConfigurable) {
            $this->currentConfigurationModel = ArrayHelper::getValue($this->currentConfigurable, 'configurationModel');
            $this->currentConfigurationView = ArrayHelper::getValue($this->currentConfigurable, 'configurationView');
        }
        if ($this->currentConfigurationView !== null && $this->currentConfigurationModel !== null) {
            $this->isValidSection = true;
            $this->currentConfigurationView = '@vendor/'
                . $this->currentConfigurable['package']
                . '/' . $this->currentConfigurationView;
            $this->model = new $this->currentConfigurationModel;
            $configurablesStatePath = ExtensionsManager::module()->configurationUpdater->configurablesStatePath;
            $this->model->loadState($configurablesStatePath);
        } else {
            $this->currentConfigurationView = '_default_configuraton.php';
        }
    }

    /**
     * @inheritdoc
     */
    public function defineParts()
    {
        return [
            'links' => [
                'function' => 'sectionLinks',
                'title' => Yii::t('extensions-manager', 'Configuration'),
                'icon' => 'fa fa-list-alt',
                'type' => TabbedFormCombinedAction::TYPE_TABS_LINKS,
            ],
            'saveData' => [
                'function' => 'saveData',
            ],
            'renderSectionForm' => [
                'function' => 'renderSectionForm',
                'title' => empty($this->currentConfigurable['sectionNameTranslated'])
                    ? Yii::t('extensions-manager', 'Nothing to configure')
                    : $this->currentConfigurable['sectionNameTranslated'],
                'icon' => 'fa fa-cogs',
                'footer' => $this->getFooter(),
            ],
        ];
    }

    /**
     * @return array
     */
    public function sectionLinks()
    {
        $navItems = ExtensionsManager::navLinks();
        $configItems = [];
        foreach ($this->configurables as $index => $item) {
            $navItem = [
                'label' => $item['sectionNameTranslated'],
                'url' => [$this->id, 'sectionIndex' => $index],
            ];
            if ($index === $this->sectionIndex) {
                $navItem['active'] = true;
            }
            $configItems[] = $navItem;
        }
        if (true === isset($navItems['config'])) {
            $navItems['config']['items'] = $configItems;
        }
        return $navItems;
    }

    /**
     * @return string|\yii\web\Response
     */
    public function saveData()
    {
        if (false === isset($this->model)) {
            return '';
        }
        if ($this->model->load(Yii::$app->request->post()) && $this->model->validate()) {
            if (ExtensionsManager::module()->configurationUpdater->updateConfiguration(true)) {
                return $this->controller->redirect([$this->id, 'sectionIndex' => $this->sectionIndex]);
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function renderSectionForm()
    {
        return $this->render(
            $this->currentConfigurationView,
            [
                'model' => $this->model,
                'configurable' => $this->currentConfigurable,
                'form' => $this->form,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getFooter()
    {
        return (null === $this->currentConfigurable)
            ? ''
            : Html::submitButton(
                '<i class="fa fa-floppy-o"></i>&nbsp;' .
                (Yii::t('extensions-manager', 'Save')),
                ['class' => 'btn btn-primary pull-right']
            );
    }

    /**
     * @inheritdoc
     */
    public function breadcrumbs()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function title()
    {
        return Yii::t('extensions-manager', 'Configuration');
    }
}
