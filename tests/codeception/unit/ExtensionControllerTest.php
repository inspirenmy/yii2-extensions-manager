<?php

namespace DevGroup\ExtensionsManager\tests;

use testsHelper\TestConfigCleaner;
use yii\console\Application;
use Yii;

class ExtensionControllerTest extends \PHPUnit_Framework_TestCase
{
    protected static $migrationPath;
    protected static function writeExtFile()
    {
        $fn = __DIR__ . '/../../testapp/config/extensions.php';
        if (true === file_exists($fn)) {
            unlink($fn);
        }
        copy(__DIR__ . '/../../data/extensions.php', $fn);
    }

    public function setUp()
    {
        self::writeExtFile();
        $config = include __DIR__ . '/../../testapp/config/console.php';
        new Application($config);
        Yii::$app->cache->flush();

        self::$migrationPath = Yii::getAlias('@vendor') . '/devgroup/yii2-deferred-tasks/src/migrations';
        Yii::$app->runAction('migrate/down', [99999, 'interactive' => 0, 'migrationPath' => self::$migrationPath]);
        Yii::$app->runAction('migrate/up', ['interactive' => 0, 'migrationPath' => self::$migrationPath]);
        Yii::setAlias('@vendor', __DIR__ . '/../../testapp/vendor');
        parent::setUp();
    }

    public function tearDown()
    {
        Yii::$app->runAction('migrate/down', [99999, 'interactive' => 0, 'migrationPath' => self::$migrationPath]);
        parent::tearDown();
        if (Yii::$app && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        Yii::$app = null;
        self::writeExtFile();
        TestConfigCleaner::cleanTestConfigs();
    }

    public function testActionActivateNonExistingExtension()
    {
        $this->assertEquals(1, Yii::$app->runAction('extension/activate', ['package/extension1']));
    }

    public function testActionDeactivateNonExistingExtension()
    {
        $this->assertEquals(1, Yii::$app->runAction('extension/deactivate', ['package/extension1']));
    }

    public function testActionActivate()
    {
        $this->assertEquals(0, Yii::$app->runAction('extension/activate', ['fakedev/yii2-fake-ext']));
    }

    public function testActionDummy()
    {
        $this->assertEquals(0, Yii::$app->runAction('extension/dummy', ['message']));
    }
}