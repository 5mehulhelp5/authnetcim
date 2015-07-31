<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Authnetcim\Block\Adminhtml\Config;

/**
 * ApiTest Class
 */
class ApiTest extends \Magento\Framework\View\Element\Text
{
    /**
     * @var string
     */
    protected $code = 'authnetcim';

    /**
     * Test the API connection and report common errors.
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function testApi()
    {
        // TODO: Make sure we're getting the right store ID here for config scope.
        /** @var \ParadoxLabs\Authnetcim\Model\Method $method */
        $method = $this->helper->getMethodInstance($this->code);
        $method->setStore($this->getStoreId());

        // Don't bother if details aren't entered.
        if ($method->getConfigData('login') == '' || $method->getConfigData('trans_key') == '') {
            return 'Enter API credentials and save to test.';
        }

        /** @var \ParadoxLabs\Authnetcim\Model\Gateway $gateway */
        $gateway = $method->gateway();

        try {
            // Run the test call -- simple profile request. It won't exist, that's okay.
            $gateway->setParameter('customerProfileId', '1');
            $gateway->getCustomerProfile();

            return __('Authorize.Net CIM connected successfully.');
        } catch (\Exception $e) {
            /**
             * Handle common configuration errors.
             */

            $result       = $gateway->getLastResponse();
            $errorCode    = $result['messages']['message']['code'];

            if (in_array($errorCode, array( 'E00005', 'E00006', 'E00007', 'E00008' ))) {
                // Bad login ID / trans key
                return __('Your API credentials are invalid. (%s)', $errorCode);
            } elseif ($errorCode == 'E00009') {
                // Test mode active
                return __(
                    'Your account has test mode enabled. It must be disabled for CIM to work properly. (%s)',
                    $errorCode
                );
            } elseif ($errorCode == 'E00044') {
                // CIM not enabled
                return __(
                    'Your account does not have CIM enabled. Please contact your Authorize.Net support rep '
                        . 'to resolve this. (%s)',
                    $errorCode
                );
            }

            return __($e->getMessage());
        }
    }
}