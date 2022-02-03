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

class SoisyApi
{
    protected $apiEndpoint;
    protected $shopId;
    protected $apiKey;

    public function __construct($api_endpoint, $shop_id, $api_key)
    {
        $this->apiEndpoint = $api_endpoint;
        $this->shopId = $shop_id;
        $this->apiKey = $api_key;
    }

    // Method: POST, PUT, GET etc
    // Data: array("param" => "value") ==> index.php?param=value
    protected function callAPI($method, $url, $data = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Auth-Token:' . $this->apiKey));

        SoisyUtility::doLog($data, 'callAPI data');

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        // Optional Authentication:
        // curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        // Check if any error occurred
        if (!curl_errno($curl)) {
            $info = curl_getinfo($curl);
            SoisyUtility::doLog($info, 'callAPI info');
        }

        curl_close($curl);

        return $result;
    }

    public function loanQuotes($amount, $instalments, $zeroInterestRate = false)
    {
        $url = $this->apiEndpoint . '/api/shops/' . $this->shopId . '/loan-quotes';
        $data = array(
            'amount' => $amount,
            'instalments' => $instalments,
            'zeroInterestRate' => $zeroInterestRate,
        );
        try {
            $response = $this->callAPI('GET', $url, $data);
            SoisyUtility::doLog($response, 'API loanQuotes');
            $response_serialized = SoisyUtility::doUnserialize($response, 'json', array('assoc' => true));
            if (is_array($response_serialized) && !isset($response_serialized['errors'])) {
                return array(
                    'status' => 'ok',
                    'data' => $response_serialized,
                );
            } else {
                SoisyUtility::doLog($response_serialized['errors'], 'ERRORS API loanQuotes');
                $errors = array();
                foreach ($response_serialized['errors'] as $error) {
                    if (is_array($error)) {
                        array_push($errors, ...$error);
                    } else {
                        array_push($errors, $error);
                    }
                }
                return array(
                    'status' => 'error',
                    'data' => implode(', ', $errors),
                );
            }
        } catch (Exception $e) {
            SoisyUtility::doLog($e->getMessage(), 'EXCEPTION API loanQuotes');
            return array(
                'status' => 'error',
                'data' => $e->getMessage(),
            );
        }
    }

    public function createOrder(
        $email,
        $firstname,
        $lastname,
        $amountInCents,
        $vatId,
        $vatCountry,
        $fiscalCode,
        $mobilePhone,
        $city,
        $province,
        $address,
        $civicNumber,
        $postalCode,
        $zeroInterestRate,
        $successUrl,
        $errorUrl,
        $callbackUrl
    ) {
        if ($amountInCents < 10000 or $amountInCents > 1500000) {
            return false;
        }
        $url = $this->apiEndpoint . '/api/shops/' . $this->shopId . '/orders';
        $data = array(
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'amount' => $amountInCents,
            'vatId' => $vatId,
            'vatCountry' => $vatCountry,
            'fiscalCode' => $fiscalCode,
            'mobilePhone' => $mobilePhone,
            'city' => $city,
            'province' => $province,
            'address' => $address,
            'civicNumber' => $civicNumber,
            'postalCode' => $postalCode,
            'zeroInterestRate' => $zeroInterestRate,
            'successUrl' => $successUrl,
            'errorUrl' => $errorUrl,
            'callbackUrl' => $callbackUrl,
        );
        try {
            $jsonResponse = $this->callAPI('POST', $url, $data);
            $response = json_decode($jsonResponse, true);
            $errors = array();
            if (isset($response['error'])) {
                $errors[] = $response['error'];
            }
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    if (is_array($error)) {
                        array_push($errors, ...$error);
                    } else {
                        array_push($errors, $error);
                    }
                }
            }
            if (empty($errors)) {
                return array(
                    'status' => 'ok',
                    'data' => $response,
                );
            } else {
                SoisyUtility::doLog($jsonResponse, 'ERRORS API createOrder');
                return array(
                    'status' => 'error',
                    'data' => implode(', ', $errors),
                );
            }
        } catch (Exception $e) {
            SoisyUtility::doLog($e->getMessage(), 'EXCEPTION API createOrder');
            return array(
                'status' => 'error',
                'data' => $e->getMessage(),
            );
        }
    }
}
