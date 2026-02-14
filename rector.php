<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
	->withPaths([
		__DIR__,
		#__DIR__ . '/bin',
		#__DIR__ . '/etc',
		#__DIR__ . '/htdocs',
		#__DIR__ . '/lib',
	])
	->withSkip([
		__DIR__ . '/vendor',	// just in case paths accidentally include it
		__DIR__ . '/lib/php/vendor',	// just in case paths accidentally include it
	])
	->withMemoryLimit('512M')	// or '384M' / '640M' — test what survives
	->withParallel(300, 1, 5)	// positional: timeoutSeconds, maxNumberOfProcesses=1 (no parallel), jobSize
	->withCache(__DIR__ . '/.rector-cache')
	->withSets([
		// PHP upgrades (safe & additive)
		//LevelSetList::UP_TO_PHP_82,
		//LevelSetList::UP_TO_PHP_83,
		//LevelSetList::UP_TO_PHP_84,	// or stop at 83 if not ready for 8.4

		//SetList::PHP_54,
		SetList::PHP_74,
		//SetList::PHP_82,
		//SetList::TYPE_DECLARATION,
		// Add more later, e.g. SetList::PHP_81, SetList::CODE_QUALITY, etc.
		// But start with one set only
	]);
