<?php

namespace HipexDeployConfiguration;

use HipexDeployConfiguration\PlatformService\RedisService;

\Deployer\set('bin/composer', '/usr/local/bin/composer2');
\Deployer\set('default_timeout', 3600);
\Deployer\set('keep_releases', 1);
$configuration = new ApplicationTemplate\Magento2(
    'git@github.com:ByteInternet/magento2.komkommer.store.git',
    ['en_US'],
    ['en_US']
);

$configuration->setPhpVersion('php81'); // @NOTE(timon): is this ok? Normally this would be php74 or something but that doesn't apply for Hypernode.

$stagingStage = $configuration->addStage('staging', 'staging.magento2.komkommer.store', 'hypernode');
$stagingStage->addServer('production1135-hypernode.hipex.io');

$productionStage = $configuration->addStage('production', 'magento2.komkommer.store', 'app');
$productionStage->addServer('hntestgroot.hypernode.io');

$configuration->setSharedFiles([
    'app/etc/env.php',
    'pub/errors/local.xml',
    '.user.ini',
    'pub/.user.ini'
]);

$configuration->setSharedFolders([
    'var/log',
    'var/session',
    'var/report',
    'var/export',
    'pub/media',
    'pub/sitemaps',
    'pub/static/_cache'
]);

$redisSession = new RedisService('session');
$redisSession->setMaxMemory('1024M');
$redisSession->setSnapshotSaveFrequency(60);
$redisSession->setStage($stagingStage);
$configuration->addPlatformService($redisSession);

return $configuration;
