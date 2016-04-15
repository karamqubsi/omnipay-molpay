<?php

namespace League\Omnipay\MOLPay\Message;

use League\Omnipay\Common\Exception\InvalidResponseException;

/**
 * MOLPay Purchase Request.
 *
 * ### Parameters
 *
 * * amount               [required] - Total amount to be paid
 * * appCode              [required] - Bank approval code
 * * domain               [required] - Merchant ID in MOLPay system
 * * errorMessage                    - Error message
 * * payDate              [required] - Date/Time of the transaction
 * * sKey                 [required] - Data integrity protection hash string.
 * * status               [required] - Payment status
 *                                     - 00 for successful payment
 *                                     - 11 for failure payment
 *                                     - 22 for pending payment
 * * transactionId        [required] - Invoice or order number from merchant system
 * * transactionReference [required] - Transaction ID generated by MOLPay
 * * verifyKey            [required] - Encrypted key generated by MOLPay
 */
class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * Get appCode.
     *
     * @return string
     */
    public function getAppCode()
    {
        return $this->getParameter('appCode');
    }

    /**
     * Set appCode.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setAppCode($value)
    {
        return $this->setParameter('appCode', $value);
    }

    /**
     * Get domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getParameter('domain');
    }

    /**
     * Set domain.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setDomain($value)
    {
        return $this->setParameter('domain', $value);
    }

    /**
     * Get errorMessage.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->getParameter('errorMessage');
    }

    /**
     * Set errorMessage.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setErrorMessage($value)
    {
        return $this->setParameter('errorMessage', $value);
    }

    /**
     * Get payDate.
     *
     * @return string
     */
    public function getPayDate()
    {
        return $this->getParameter('payDate');
    }

    /**
     * Set payDate.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setPayDate($value)
    {
        return $this->setParameter('payDate', $value);
    }

    /**
     * Get sKey.
     *
     * @return string
     */
    public function getSKey()
    {
        return $this->getParameter('sKey');
    }

    /**
     * Set sKey.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setSKey($value)
    {
        return $this->setParameter('sKey', $value);
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getParameter('status');
    }

    /**
     * Set status.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setStatus($value)
    {
        return $this->setParameter('status', $value);
    }

    /**
     * Get transactionReference.
     *
     * @return string
     */
    public function getTransactionReference()
    {
        return $this->getParameter('transactionReference');
    }

    /**
     * Set transactionReference.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setTransactionReference($value)
    {
        return $this->setParameter('transactionReference', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (null !== $this->getErrorMessage()) {
            throw new InvalidResponseException($this->getErrorMessage());
        }

        $this->validate('sKey');

        $sKey = $this->getSKey(); // Security key generated by MOLPay

        $generatedSKey = $this->generateSKey(); // Security key generated by merchant

        if ($sKey != $generatedSKey) {
            throw new InvalidResponseException('Invalid security key');
        }

        return array(
            'status' => $this->getStatus(),
            'transactionId' => $this->getTransactionId(),
            'transactionReference' => $this->getTransactionReference(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sendData($data)
    {
        // Acknowledge MOLPay via IPN
        if (true === $this->getEnableIPN()) {
            $this->sendIPN();
        }

        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    /**
     * Generate pre-sKey - first hash encryption.
     *
     * @return string
     */
    protected function generatePreSKey()
    {
        $this->validate('amount', 'currency', 'domain', 'status', 'transactionId', 'transactionReference');

        return md5($this->getTransactionReference().$this->getTransactionId().$this->getStatus().$this->getDomain().$this->getAmount()->getFormatted().$this->convertCurrency());
    }

    /**
     * Generate sKey - final hash encryption.
     *
     * @return string
     */
    protected function generateSKey()
    {
        $this->validate('appCode', 'domain', 'payDate', 'verifyKey');

        return md5($this->getPayDate().$this->getDomain().$this->generatePreSKey().$this->getAppCode().$this->getVerifyKey());
    }

    /**
     * Convert currency to 'MOLPay''s style currency.
     * @NOTE Funny enough, MOLPay returns currency as 'RM' not 'MYR'. And I am not sure about other currencies though.
     *
     * @return string
     */
    private function convertCurrency()
    {
        return $this->getCurrency() !== 'MYR' ? $this->getCurrency() : 'RM';
    }
}
