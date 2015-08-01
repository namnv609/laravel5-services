<?php

namespace App\Services;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\PaymentExecution;
use Request;

class PayPalService
{
    /**
     * @var $apiContext
     */
    private $apiContext;

    /**
     * @var $itemList
     */
    private $itemList;

    /**
     * @var $paymentCurrency
     */
    private $paymentCurrency;

    /**
     * @var $totalAmount
     */
    private $totalAmount;

    /**
     * @var $returnUrl
     */
    private $returnUrl;

    /**
     * @var $cancelUrl
     */
    private $cancelUrl;

    public function __construct()
    {
        $paypalConfigs = config('paypal');

        $this->apiContext = new ApiContext(
            new OAuthTokenCredential($paypalConfigs['client_id'], $paypalConfigs['secret'])
        );
        $this->apiContext->setConfig($paypalConfigs['settings']);

        $this->paymentCurrency = "USD";
        $this->totalAmount = 0;
    }

    /**
     * Set payment currency
     *
     * @param string $currency String name of currency
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->paymentCurrency = strtoupper($currency);
        return $this;
    }

    /**
     * Get current payment currency
     *
     * @return string Current payment currency
     */
    public function getCurrency()
    {
        return $this->paymentCurrency;
    }

    /**
     * Add item to list
     *
     * @param array $itemData Array item data
     * @return self
     */
    public function setItem($itemData)
    {
        // Check parameter is single or multiple dimensional
        if (count($itemData) === count($itemData, COUNT_RECURSIVE)) {
            $itemData = [$itemData];
        }

        foreach ($itemData as $data) {
            $item = new Item();

            $item->setName($data['name'])
                 ->setCurrency($this->paymentCurrency)
                 ->setSku($data['sku'])
                 ->setQuantity($data['quantity'])
                 ->setPrice($data['price']);

            $this->itemList[] = $item;
            $this->totalAmount += $data['price'] * $data['quantity'];
        }

        return $this;
    }

    /**
     * Get list item
     *
     * @return array List item
     */
    public function getItemList()
    {
        return $this->itemList;
    }

    /**
     * Get total amount
     *
     * @return int Total amount
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set return URL
     *
     * @param string $url Return URL for payment process complete
     * @return self
     */
    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;

        return $this;
    }

    /**
     * Get return URL
     *
     * @return string Return URL
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * Set cancel URL
     *
     * @param $url Cancel URL for payment
     * @return self
     */
    public function setCancelUrl($url)
    {
        $this->cancelUrl = $url;

        return $this;
    }

    /**
     * Get cancel URL of payment
     *
     * @return string Cancel URL
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * Create payment
     *
     * @param string $transactionDescription Description for transaction
     * @return mixed Paypal checkout URL or false
     */
    public function createPayment($transactionDescription)
    {
        $checkoutUrl = false;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $itemList = new ItemList();
        $itemList->setItems($this->itemList);

        $amount = new Amount();
        $amount->setCurrency($this->paymentCurrency)
               ->setTotal($this->totalAmount);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription($transactionDescription);

        $redirectUrls = new RedirectUrls();

        // Check $cancelUrl. If it's null. By default, cancel URL
        // same as success URL
        if (is_null($this->cancelUrl)) {
            $this->cancelUrl = $this->returnUrl;
        }

        $redirectUrls->setReturnUrl($this->returnUrl)
                     ->setCancelUrl($this->cancelUrl);

        $payment = new Payment();
        $payment->setIntent('Sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions([$transaction]);

        try {
            $payment->create($this->apiContext);
        } catch (\PayPal\Exception\PPConnectionException $paypalException) {
            throw new \Exception($paypalException->getMessage());
        }

        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $checkoutUrl = $link->getHref();

                session(['paypal_payment_id' => $payment->getId()]);

                break;
            }
        }

        return $checkoutUrl;
    }

    /**
     * Get payment status
     *
     * @return mixed Object payment details or false
     */
    public function getPaymentStatus()
    {
        $request = Request::all();

        $paymentId = session('paypal_payment_id');
        session()->forget('paypal_payment_id');

        if (empty($request['PayerID']) || empty($request['token'])) {
            return false;
        }

        $payment = Payment::get($paymentId, $this->apiContext);

        $paymentExecution = new PaymentExecution();
        $paymentExecution->setPayerId($request['PayerID']);

        $paymentStatus = $payment->execute($paymentExecution, $this->apiContext);

        return $paymentStatus;
    }

    /**
     * Get payment details
     *
     * @param string $paymentId PayPal payment Id
     * @return mixed Object payment details
     */
    public function getPaymentDetails($paymentId)
    {
        try {
            $paymentDetails = Payment::get($paymentId, $this->apiContext);
        } catch (\PayPal\Exception\PPConnectionException $paypalException) {
            throw new \Exception($paypalException->getMessage());
        }

        return $paymentDetails;
    }

    /**
     * Get payment list
     *
     * @param int $limit Limit number payment
     * @param int $offset Start index payment
     * @return mixed Object payment list
     */
    public function getPaymentList($limit = 10, $offset = 0)
    {
        $params = [
            'count' => $limit,
            'start_index' => $offset
        ];

        try {
            $payments = Payment::all($params, $this->apiContext);
        } catch (\PayPal\Exception\PPConnectionException $paypalException) {
            throw new \Exception($paypalException->getMessage());
        }

        return $payments;
    }
}
