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

namespace ParadoxLabs\Authnetcim\Model;

use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Payment\Model\CcConfig;

/**
 * ConfigProvider Class
 */
class ConfigProvider extends CcGenericConfigProvider
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \ParadoxLabs\Authnetcim\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @param CcConfig $ccConfig
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \ParadoxLabs\Authnetcim\Helper\Data $dataHelper
     * @param array $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Payment\Model\Config $paymentConfig,
        \ParadoxLabs\Authnetcim\Helper\Data $dataHelper,
        array $methodCodes = ['authnetcim']
    ) {
        $this->paymentHelper    = $paymentHelper;
        $this->checkoutSession  = $checkoutSession;
        $this->customerSession  = $customerSession;
        $this->dataHelper       = $dataHelper;
        $this->paymentConfig    = $paymentConfig;

        $this->method           = $this->paymentHelper->getMethodInstance('authnetcim');

        parent::__construct($ccConfig, $paymentHelper, $methodCodes);
    }

    /**
     * Returns applicable stored cards
     *
     * @return array
     */
    public function getStoredCards()
    {
        return $this->dataHelper->getActiveCustomerCardsByMethod('authnetcim');
    }

    /**
     * If card can be saved for further use
     *
     * @return boolean
     */
    public function canSaveCard()
    {
        if ($this->customerSession->isLoggedIn()) {
            return true;
        }
        return false;
    }

    /**
     * @return array|void
     */
    public function getConfig()
    {
        if (!$this->method->isAvailable()) {
            return [];
        }

        $config             = parent::getConfig();
        $selected           = null;
        $storedCardOptions  = [];
        $cards              = $this->getStoredCards();

        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        foreach ($cards as $card) {
            $storedCardOptions[]    = [
                'id'       => $card->getHash(),
                'label'    => $card->getLabel(),
                'selected' => false,
                'type'     => $card->getAdditional('cc_type'),
            ];

            $selected               = $card->getHash();
        }

        $config = array_merge_recursive($config, [
            'payment' => [
                'authnetcim' => [
                    'useVault'                => true,
                    'canSaveCard'             => $this->canSaveCard(),
                    'forceSaveCard'           => $this->forceSaveCard(),
                    'storedCards'             => $storedCardOptions,
                    'selectedCard'            => $selected,
                    'isCcDetectionEnabled'    => true,
                    'availableCardTypes'      => $this->getCcAvailableTypes('authnetcim'),
                    'logoImage'               => $this->getLogoImage(),
                    'requireCcv'              => $this->requireCcv(),
                ],
            ],
        ]);

        return $config;
    }

    /**
     * Whether to give customers the 'save this card' option, or just assume yes.
     *
     * @return bool
     */
    public function forceSaveCard()
    {
        return $this->method->getConfigData('allow_unsaved') ? false : true;
    }

    /**
     * Whether to force customers to enter CCV when using a stored card.
     *
     * @return bool
     */
    public function requireCcv()
    {
        return $this->method->getConfigData('require_ccv') ? true : false;
    }

    /**
     * Get payment method logo URL (if enabled)
     *
     * @return string|false
     */
    public function getLogoImage()
    {
        if ($this->method->getConfigData('show_branding')) {
            return $this->ccConfig->getViewFileUrl('ParadoxLabs_Authnetcim::images/logo.png');
        }

        return false;
    }
}