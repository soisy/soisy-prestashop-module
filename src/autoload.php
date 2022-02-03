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

$models = scandir(_PS_MODULE_DIR_ . '/soisy/models/');
foreach ($models as $model) {
    if ($model !== '.' && $model !== '..' && $model !== 'index.php' && mb_substr($model, -4) === '.php') {
        require_once('models/' . $model);
    }
}

$classes = scandir(_PS_MODULE_DIR_ . '/soisy/classes/');
foreach ($classes as $class) {
    if ($class != '.' && $class != '..' && $class != 'index.php'  && mb_substr($class, -4) === '.php') {
        require_once('classes/' . $class);
    }
}

require_once('src/autoload.php');
