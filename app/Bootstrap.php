<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator();

		$configurator->setDebugMode(\getenv('NETTE_DEBUG') === '1'); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');

		$configurator
			->addConfig(__DIR__ . '/../config/common.neon')
			->addConfig(__DIR__ . '/../config/local.neon');

		return $configurator;
	}

	public static function bootForTests(): Configurator
	{
		$configurator = self::boot();
		\Tester\Environment::setup();

		return $configurator;
	}
}
