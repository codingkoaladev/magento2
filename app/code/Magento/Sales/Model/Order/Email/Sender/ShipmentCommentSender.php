<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Model\Order\Email\Sender;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\ShipmentCommentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\NotifySender;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\App\Emulation;

class ShipmentCommentSender extends NotifySender
{
    /**
     * @var Renderer
     */
    protected $addressRenderer;

    /**
     * Application Event Dispatcher
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @param Template $templateContainer
     * @param ShipmentCommentIdentity $identityContainer
     * @param Order\Email\SenderBuilderFactory $senderBuilderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Renderer $addressRenderer
     * @param ManagerInterface $eventManager
     * @param Emulation|null $appEmulation
     */
    public function __construct(
        Template $templateContainer,
        ShipmentCommentIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        ManagerInterface $eventManager,
        ?Emulation $appEmulation = null
    ) {
        parent::__construct($templateContainer, $identityContainer, $senderBuilderFactory, $logger, $addressRenderer);
        $this->addressRenderer = $addressRenderer;
        $this->eventManager = $eventManager;
        $this->appEmulation = $appEmulation ?: ObjectManager::getInstance()->get(Emulation::class);
    }

    /**
     * Send email to customer
     *
     * @param Shipment $shipment
     * @param bool $notify
     * @param string $comment
     * @return bool
     */
    public function send(Shipment $shipment, $notify = true, $comment = '')
    {
        $order = $shipment->getOrder();
        $this->identityContainer->setStore($order->getStore());

        $this->appEmulation->startEnvironmentEmulation($order->getStoreId(), Area::AREA_FRONTEND, true);
        $transport = [
            'order' => $order,
            'shipment' => $shipment,
            'comment' => $comment,
            'billing' => $order->getBillingAddress(),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $transportObject = new DataObject($transport);
        $this->appEmulation->stopEnvironmentEmulation();
        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_shipment_comment_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        return $this->checkAndSend($order, $notify);
    }
}
