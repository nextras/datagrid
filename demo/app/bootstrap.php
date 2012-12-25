<?php

use Nette\Application\Routers\Route;


require __DIR__ . '/../libs/autoload.php';



$configurator = new Nette\Config\Configurator;
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(APP_DIR . '/../../Nextras')
	->addDirectory(APP_DIR)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$container = $configurator->createContainer();

$container->router[] = new Route('<action>', 'Demo:default');

$container->application->run();
