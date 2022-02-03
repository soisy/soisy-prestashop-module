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

namespace Soisy\Plugins\SoisyPlugin\Models;

class LoanModel
{
    const CUSTOMER_REDIRECTED = 'Customer redirected';
    const REQUEST_PREAUTHORIZED = 'Request pre-authorized';
    const REQUEST_COMPLETED = 'Request completed';
    const APPROVED = 'Approved';
    const PAID = 'Paid';
    const REJECTED = 'Rejected';

    /** @var int */
    private $id;

    /** @var int */
    private $shopOrderId;

    /** @var string */
    private $state;

    /** @var string */
    private $context;

    /**
     * @param int    $id
     * @param int    $shopOrderId
     * @param string $state
     * @param string $context
     */
    public function __construct($id, $shopOrderId, $state, $context)
    {
        $this->id = $id;
        $this->shopOrderId = $shopOrderId;
        $this->state = $state;
        $this->context = $context;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @return int|null */
    public function getShopOrderId()
    {
        return $this->shopOrderId;
    }

    /** @return string */
    public function getState()
    {
        return $this->state;
    }

    /** @return string */
    public function getContext()
    {
        return $this->context;
    }
}
