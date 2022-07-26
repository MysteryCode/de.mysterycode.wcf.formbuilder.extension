<?php

/** @var ScriptPackageInstallationPlugin $this */

use wcf\system\package\plugin\ScriptPackageInstallationPlugin;
use wcf\system\WCF;
use wcf\util\FileUtil;

$packageID = $this->installation->getPackageID();

$statement = WCF::getDB()->prepare(
    '
    SELECT  *
    FROM    wcf1_package_installation_file_log
    WHERE   packageID = ?
'
);
$statement->execute([$packageID]);

while ($row = $statement->fetchArray()) {
    if (empty($row['filename']) || !\defined(\mb_strtoupper($row['application']) . '_DIR')) {
        continue;
    }

    $filename = FileUtil::getRealPath(\constant(\mb_strtoupper($row['application']) . '_DIR')) . $row['filename'];

    if (\file_exists($filename)) {
        \unlink($filename);
    }
}

WCF::getDB()->prepare(
    '
    DELETE 
    FROM   wcf1_package_installation_file_log
    WHERE  packageID = ?
'
)->execute([$packageID]);

$statement = WCF::getDB()->prepare(
    '
    SELECT  *
    FROM    wcf1_acp_template
    WHERE   packageID = ?
'
);
$statement->execute([$packageID]);

while ($row = $statement->fetchArray()) {
    if (empty($row['templateName']) || !\defined(\mb_strtoupper($row['application']) . '_DIR')) {
        continue;
    }

    $filename = FileUtil::getRealPath($row['application']) . 'acp/templates/' . $row['templateName'];

    if (\file_exists($filename)) {
        \unlink($filename);
    }
}

WCF::getDB()->prepare(
    '
    DELETE
    FROM wcf1_acp_template WHERE packageID = ?
'
)->execute([$packageID]);

$statement = WCF::getDB()->prepare(
    '
    SELECT  *
    FROM    wcf1_template
    WHERE   packageID = ?
'
);
$statement->execute([$packageID]);

while ($row = $statement->fetchArray()) {
    if (empty($row['templateName']) || !\defined(\mb_strtoupper($row['application']) . '_DIR')) {
        continue;
    }

    $filename = FileUtil::getRealPath(
        \constant(\mb_strtoupper($row['application']) . '_DIR')
    ) . 'templates/' . $row['templateName'];

    if (\file_exists($filename)) {
        \unlink($filename);
    }
}

WCF::getDB()->prepare(
    '
    DELETE FROM wcf1_template WHERE packageID = ?
'
)->execute([$packageID]);
