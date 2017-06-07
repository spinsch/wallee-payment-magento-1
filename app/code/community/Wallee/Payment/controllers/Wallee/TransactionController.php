<?php

/**
 * Wallee Magento
 *
 * This Magento extension enables to process payments with Wallee (https://wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 * @link https://github.com/wallee-payment/magento
 */

/**
 * This controller provides actions regarding the transaction.
 */
class Wallee_Payment_Wallee_TransactionController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order');
    }

    /**
     * Sends a refund request to the gateway.
     */
    public function refundAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        /* @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');

        /* @var Wallee_Payment_Model_Entity_RefundJob $existingRefundJob */
        $existingRefundJob = Mage::getModel('wallee_payment/entity_refundJob');
        $existingRefundJob->loadByOrder($orderId);
        if ($existingRefundJob->getId() > 0) {
            try {
                /* @var Wallee_Payment_Model_Service_Refund $refundService */
                $refundService = Mage::getSingleton('wallee_payment/service_refund');
                $refund = $refundService->refund($existingRefundJob->getSpaceId(), $existingRefundJob->getRefund());

                if ($refund->getState() == \Wallee\Sdk\Model\Refund::STATE_FAILED) {
                    $session->addError(
                        Mage::helper('wallee_payment')->translate(
                            $refund->getFailureReason()
                            ->getDescription()
                        )
                    );
                } elseif ($refund->getState() == \Wallee\Sdk\Model\Refund::STATE_PENDING) {
                    $session->addNotice(Mage::helper('wallee_payment')->__('The refund was requested successfully, but is still pending on the gateway.'));
                } else {
                    $session->addSuccess('Successfully refunded.');
                }
            } catch (Exception $e) {
                $session->addError('There has been an error while sending the refund to the gateway.');
            }
        } else {
            $session->addError('For this order no refund request exists.');
        }

        $this->_redirect(
            'adminhtml/sales_order/view', array(
            'order_id' => $orderId
            )
        );
    }

    /**
     * Downloads the transaction's invoice PDF document.
     */
    public function downloadInvoiceAction()
    {
        $spaceId = $this->getRequest()->getParam('space_id');
        $transactionId = $this->getRequest()->getParam('transaction_id');

        $service = new \Wallee\Sdk\Service\TransactionService(Mage::helper('wallee_payment')->getApiClient());
        $document = $service->getInvoiceDocument($spaceId, $transactionId);
        $this->download($document);
    }

    /**
     * Downloads the transaction's packing slip PDF document.
     */
    public function downloadPackingSlipAction()
    {
        $spaceId = $this->getRequest()->getParam('space_id');
        $transactionId = $this->getRequest()->getParam('transaction_id');

        $service = new \Wallee\Sdk\Service\TransactionService(Mage::helper('wallee_payment')->getApiClient());
        $document = $service->getPackingSlip($spaceId, $transactionId);
        $this->download($document);
    }

    /**
     * Downloads the refund PDF document.
     */
    public function downloadRefundAction()
    {
        $spaceId = $this->getRequest()->getParam('space_id');
        $externalId = $this->getRequest()->getParam('external_id');

        /* @var Wallee_Payment_Model_Service_Refund $refundService */
        $refundService = Mage::getSingleton('wallee_payment/service_refund');
        $refund = $refundService->getRefundByExternalId($spaceId, $externalId);

        $service = new \Wallee\Sdk\Service\RefundService(Mage::helper('wallee_payment')->getApiClient());
        $document = $service->getRefundDocument($spaceId, $refund->getId());
        $this->download($document);
    }

    /**
     * Sends the data received by calling the given path to the browser.
     *
     * @param string $path
     */
    protected function download(\Wallee\Sdk\Model\RenderedDocument $document)
    {
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', 'application/pdf', true)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $document->getTitle() . '.pdf')
            ->setHeader('Content-Description', $document->getTitle());
        $this->getResponse()->setBody(base64_decode($document->getData()));

        $this->getResponse()->sendHeaders();
        session_write_close();
        $this->getResponse()->outputBody();
    }
}