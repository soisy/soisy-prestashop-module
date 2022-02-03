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

class SoisyOrder extends ObjectModel
{
    public static $ORDER_PLACED = 'ORDER PLACED';

    public $id;
    public $id_shop;
    public $id_cart;
    public $id_order;
    public $order_reference;
    public $id_customer;
    public $id_payment;
    public $context;
    public $token;
    public $total_cart;
    public $total_paid;
    public $last_event_id;
    public $callbacks_history;
    public $sandbox;

    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'soisy_order',
        'primary' => 'id_soisy_order',
        'fields' => array(
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),
            'order_reference' => array('type' => self::TYPE_STRING, 'size' => 18, 'required' => false),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false),
            'id_payment' => array('type' => self::TYPE_STRING, 'size' => 128, 'required' => true),
            'context' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => false),
            'token' => array('type' => self::TYPE_STRING, 'size' => 255, 'required' => true),
            'total_cart' => array(
                'type' => self::TYPE_FLOAT,
                'shop' => true,
                'validate' => 'isPrice',
                'required' => true
            ),
            'total_paid' => array(
                'type' => self::TYPE_FLOAT,
                'shop' => true,
                'validate' => 'isPrice',
                'required' => true
            ),
            'last_event_id' => array('type' => self::TYPE_STRING, 'size' => 255, 'required' => true),
            'callbacks_history' => array('type' => self::TYPE_STRING, 'required' => true),
            'sandbox' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public static function createHistoryFromPast($newHistoryData, $jsonCallbacksHistory = null)
    {
        $callbacksHistory = $jsonCallbacksHistory ? json_decode($jsonCallbacksHistory) : array();
        array_unshift($callbacksHistory, $newHistoryData);
        return json_encode($callbacksHistory);
    }

    /**
     * @param string $token
     * @return SoisyOrder|null
     */
    public static function findSoisyOrderByToken($token)
    {
        $id = Db::getInstance()->getValue(
            'SELECT o.id_soisy_order FROM ' . _DB_PREFIX_ . 'soisy_order o
            WHERE o.token = "' . pSQL($token) . '"'
        );
        if (!$id) {
            return null;
        }
        return new SoisyOrder($id);
    }

    /**
     * @param int $cartId
     * @return SoisyOrder|null
     */
    public static function findSoisyOrderByCartId($cartId)
    {
        $id = Db::getInstance()->getValue(
            'SELECT o.id_soisy_order FROM ' . _DB_PREFIX_ . 'soisy_order o
            WHERE o.id_cart = "' . pSQL($cartId) . '"'
        );
        if (!$id) {
            return null;
        }
        return new SoisyOrder($id);
    }
}
