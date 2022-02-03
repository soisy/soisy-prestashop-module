<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Soisy
 * @copyright 2007-2022 Soisy
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Soisy
 */

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'soisy_order` (
    `id_soisy_order` int(11) NOT NULL AUTO_INCREMENT,
    `id_shop` int(10) NULL,
    `id_cart` int(10) UNSIGNED NOT NULL,
    `id_order` INT(10) NULL,
    `order_reference` char(18) NULL,
    `id_customer` INT(10) NULL,
    `id_payment` varchar(128) NOT NULL,
    `context` MEDIUMTEXT NULL,
    `token` varchar(255) NOT NULL,
    `total_cart` decimal(10,2) NOT NULL,
    `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
    `last_event_id` varchar(255) NOT NULL DEFAULT \'\',
    `callbacks_history` TEXT NOT NULL DEFAULT \'\',
    `sandbox` tinyint(1) UNSIGNED NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_soisy_order`),
	UNIQUE (`token`),
    UNIQUE (`id_cart`),
    INDEX `id_order` (`id_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (empty(Db::getInstance()->execute($query))) {
        return false;
    }
}
