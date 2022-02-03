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

class SoisyUtility
{
    public static function getConfigurationWithDefault($key, $default)
    {
        $res = Configuration::get($key, 0);
        if ($res === false) {
            $res = $default;
        }
        return $res;
    }

    public static function getThemesOptions()
    {
        $themes = Theme::getAvailable(true);
        $toReturn = array();

        foreach ($themes as $theme) {
            $toReturn[] = array(
                'id_option' => $theme,
                'name' => $theme
            );
        }

        return $toReturn;
    }

    // Log in separate file for debug
    public static function doLog($text, $context = '', $prefix = 'debug', $subdir = '', $debug_ip_arr = array())
    {
        // Enable logs only
        if (!Configuration::get('SOISY_LOG_ENABLED')) {
            return false;
        }

        if (!empty($debug_ip_arr)) {
            if (!in_array($_SERVER['REMOTE_ADDR'], $debug_ip_arr)) {
                return false;
            }
        }

        $module_name = 'soisy'; // module name
        $dir = _PS_MODULE_DIR_ . $module_name . "/logs";

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755) && !is_dir($dir)) {
                return false;
            }
        }

        if (!file_exists($dir)) {
            return false;
        }

        if (!empty($subdir)) {
            $subdir = $dir . '/' . $subdir;
            if (!is_dir($subdir)) {
                if (!mkdir($subdir, 0755) && !is_dir($subdir)) {
                    return false;
                }
            }

            if (!file_exists($subdir)) {
                return false;
            }

            $dir .= '/' . $subdir;
        }

        $timestamp = date("Ymd");
        $filename = $dir . DIRECTORY_SEPARATOR . $prefix . "_log_" . $timestamp . ".log";

        // Get only object vars
        if (is_object($text)) {
            $text = get_object_vars($text);
        }

        // open log file
        $fh = fopen($filename, "a");
        fwrite($fh, date("Y-m-d, H:i:s") . "\n");
        if (!empty($context)) {
            fwrite($fh, '*** CONTEXT: ' . $context . ' ***' . "\n");
        }
        fwrite($fh, print_r($text, true) . "\n");
        fclose($fh);
    }

    /**
     * Get a more general representation of the current PrestaShop version
     *
     * @return string `15` or `16`, `17`
     * @throws PrestaShopException when a non-supported version is detected.
     */
    public static function getPSVersion()
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return '17';
        }

        if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
            return '16';
        }

        if (Tools::version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            return '15';
        }

        throw new PrestaShopException('Unsupported PrestaShop version.');
    }

    public static function doSerialize($obj, $type = 'json')
    {
        if ($type === 'json') {
            return json_encode(
                $obj,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
            );
        }

        return false;
    }

    public static function doUnserialize($strobj, $type = 'json', $params = '')
    {
        if ($type === 'json') {
            return json_decode($strobj, isset($params['assoc']) ? $params['assoc'] : false);
        }

        return false;
    }

    public static function checkCarrierReferenceByIdOrder($id_order, $id_reference_target)
    {
        if (!empty($id_order)) {
            $order = new Order($id_order);
            if (Validate::isLoadedObject($order)) {
                if (!empty($order->id_carrier)) {
                    $carrier = new Carrier($order->id_carrier);
                    if (Validate::isLoadedObject($carrier)) {
                        if ((int)$carrier->id_reference === (int)$id_reference_target) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function formatEurPrice($price)
    {
        return str_replace('.', ',', $price) . ' €';
    }
}
