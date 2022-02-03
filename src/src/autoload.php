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

require_once 'Models/LoanCreationModel.php';
require_once 'Models/LoanModel.php';
require_once 'Models/OrderCreationModel.php';
require_once 'Exceptions/LoanControllerException.php';
require_once 'Interfaces/ILoanRepository.php';
require_once 'Interfaces/ILogger.php';
require_once 'Interfaces/IShopApi.php';
require_once 'Interfaces/IShopOrder.php';
require_once 'Interfaces/ITranslator.php';
require_once 'Implementations/prestashop/PsLoanRepository.php';
require_once 'Implementations/prestashop/PsLogger.php';
require_once 'Implementations/prestashop/PsShopApi.php';
require_once 'Implementations/prestashop/PsTranslator.php';
require_once 'LoanController.php';
