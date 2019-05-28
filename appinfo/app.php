<?php

namespace OCA\Carnet\AppInfo;
use OCP\AppFramework\App;
use OCA\Mail\HordeTranslationHandler;
use OCA\Carnet\Hooks\FSHooks;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\AppFramework\IAppContainer;
use OCP\Util;
use OCP\IDBConnection;

if ((@include_once __DIR__ . '/../vendor/autoload.php')===false) {
	throw new Exception('Cannot include autoload. Did you run install dependencies using composer?');
}
class Application extends App {

    public function __construct(array $urlParams=array()){
        parent::__construct('carnet', $urlParams);
        $container = $this->getContainer();
        $container->registerService('Config', function($c) {

            return $c->query('ServerContainer')->getConfig();
        });

        $container->registerService('RootFolder', function($c) {

            return $c->query('ServerContainer')->getRootFolder();
        });
        $container->registerService('UserManager', function($c) {
            return $c->query('ServerContainer')->getUserManager();
        });
    }

    public function connectWatcher(IAppContainer $container) {
            /** @var IRootFolder $root */
            $root = $container->query(IRootFolder::class);
            $root->listen('\OC\Files', 'postWrite', function (Node $node) use ($container) {
                $c = $container->query('ServerContainer'); 
                $user = $c->getUserSession()->getUser();
                if($user != null){
                    $watcher = new FSHooks($c->getUserFolder(), $user->getUID(), $c->getConfig(), 'carnet',$container->query(IDBConnection::class));
                    $watcher->postWrite($node);
                 }
            });
            $root->listen('\OC\Files', 'postDelete', function (Node $node) use ($container) {
                $c = $container->query('ServerContainer');
                $user = $c->getUserSession()->getUser();
                if($user != null){
                    $watcher = new FSHooks($c->getUserFolder(), $user->getUID(), $c->getConfig(), 'carnet',$container->query(IDBConnection::class));
                    $watcher->postDelete($node);
                 }
            });
    }
}
$app = new Application();
$container = $app->getContainer();

$app->connectWatcher($container);

$appName = $container->query('AppName');
$container->query('OCP\INavigationManager')
    ->add(
        function () use ($container, $appName) {
            $urlGenerator = $container->query('OCP\IURLGenerator');

            return [
                        'id'    => $appName,

                        // Sorting weight for the navigation. The higher the number, the higher
                        // will it be listed in the navigation
                        'order' => 2,

                        // The route that will be shown on startup when called from within the GUI
                        // Public links are using another route, see appinfo/routes.php
                        'href'  => $urlGenerator->linkToRoute($appName . '.page.index'),

                        // The icon that will be shown in the navigation
                        // This file needs to exist in img/
                        'icon'  => $urlGenerator->imagePath($appName, 'app.svg'),

                        // The title of the application. This will be used in the
                        // navigation or on the settings page
                        'name'  => 'Carnet'
                ];
        }
);

?>