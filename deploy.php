<?php

declare(strict_types=1);

namespace Hypernode\DeployConfiguration;

use Hypernode\DeployConfiguration\PlatformConfiguration\HypernodeSettingConfiguration;

use function Deployer\{after, before, invoke, run, task};

$configuration = new ApplicationTemplate\Magento2(['en_US']);
$configuration->addPlatformConfiguration(
    new HypernodeSettingConfiguration('php_version', '8.3')
);

task('magento:prepare_env:test', static function () {
    run('cp ~/apps/magento2.komkommer.store/shared/app/etc/env.php {{release_path}}/app/etc/env.php');
    run('cd {{release_path}}; n98-magerun2 config:env:set db.connection.default.host mysqlmaster');
    invoke('magento:cache:flush');
})->select('stage=test');

task('magento:configure_env:test', static function () {
    run('{{bin/php}} {{release_path}}/bin/magento config:set web/unsecure/base_url https://{{hostname}}/');
    run('{{bin/php}} {{release_path}}/bin/magento config:set web/secure/base_url https://{{hostname}}/');
    invoke('magento:cache:flush');
})->select('stage=test');

task('hmv:configure:test', static function () {
    run('hypernode-manage-vhosts {{hostname}} --https --force-https --type magento2 --webroot {{current_path}}/{{public_folder}}');
})->select('stage=test');

before('magento:config:import', 'magento:prepare_env:test');
after('magento:config:import', 'magento:configure_env:test');

$configuration->addDeployTask('hmv:configure:test');

$stagingStage = $configuration->addStage('staging', 'staging.magento2.komkommer.store', 'hypernode');
$stagingStage->addServer('production1135-hypernode.hipex.io');

$productionStage = $configuration->addStage('production', 'magento2.komkommer.store');
$productionStage->addServer('hntestgroot.hypernode.io');

$testStage = $configuration->addStage('test', 'test.komkommer.store');
$testStage->addBrancherServer('hntestgroot')
    ->setLabels(['stage=test', 'ci_ref=' . (\getenv('GITHUB_HEAD_REF') ?: 'none')]);

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

return $configuration;
