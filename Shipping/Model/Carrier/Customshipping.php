<?php

namespace Dtn\Shipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\SessionFactory;

class Customshipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var \Magento\Checkout\Model\CartFactory
     */
    protected $cartFactory;

    /**
     * @var SessionFactory
     */
    protected $sessionFactory;

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'Dtn_Shipping';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * Customshipping constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param SessionFactory $sessionFactory
     * @param \Magento\Checkout\Model\CartFactory $cartFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        SessionFactory $sessionFactory,
        \Magento\Checkout\Model\CartFactory $cartFactory,
        array $data = []
    )
    {
        $this->sessionFactory = $sessionFactory;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->cartFactory = $cartFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */

    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * Collect and get rates for storefront
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */

    public function collectRates(RateRequest $request)
    {
        /**
         * Make sure that Shipping method is enabled
         */

        if (!$this->isActive()) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        $shippingPrice = $this->getConfigData('price');

        $method = $this->_rateMethodFactory->create();

        /**
         * Set carrier's method data
         */
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('title'));

//        /**
//         * Displayed as shipping method under Carrier
//         */
//        $method->setMethod($this->_code);
//        $method->setMethodTitle($this->getConfigData('name'));


        /**
         * Get Total Order Price in cart
         */
        $quote = $this->cartFactory->create()->getQuote();
        $price = $quote->getGrandTotal();
        $prToFree = $this->getConfigData('freeshipping');

        if ($price > $prToFree) {
            $shippingPrice = 0;
            $remainPrice =  'Best Way';
        }
        else{
            $remainPrice =  'Need ' . ($prToFree - $price) . '$ to freeship';
        }

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($remainPrice);

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        $result->append($method);

        return $result;
    }
}