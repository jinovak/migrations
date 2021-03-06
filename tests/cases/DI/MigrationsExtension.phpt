<?php

/**
 * Test: DI\MigrationsExtension
 */

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Nettrine\Migrations\ContainerAwareConfiguration;
use Nettrine\Migrations\DI\MigrationsExtension;
use Tester\Assert;
use Tester\FileMock;

require_once __DIR__ . '/../../bootstrap.php';

test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		//Required services and params
		$compiler->loadConfig(FileMock::create('
			parameters:
				appDir: "/srv/app"

			services:
			 - Doctrine\DBAL\Driver\Mysqli\Driver
			 - Doctrine\DBAL\Connection([])
		', 'neon'));

		//Migrations
		$compiler->addExtension('migrations', new MigrationsExtension());
		$compiler->loadConfig(FileMock::create('
			migrations:
				table: doctrine_migrations
				column: version
				directory: %appDir%/../migrations
				namespace: Migrations
				versionsOrganization: null
		', 'neon'));
	}, '1a');

	/** @var Container $container */
	$container = new $class;

	/** @var ContainerAwareConfiguration $awareConfiguration */
	$awareConfiguration = $container->getByType(ContainerAwareConfiguration::class);
	Assert::equal('Migrations', $awareConfiguration->getMigrationsNamespace());
	Assert::equal('/srv/app/../migrations', $awareConfiguration->getMigrationsDirectory());
	Assert::count(8, $container->findByType(AbstractCommand::class));
});
