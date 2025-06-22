<?php

namespace Gets\QliroApi\Api\Resources\Admin;

use Gets\QliroApi\Api\QliroConnector;
use Gets\QliroApi\Api\Requests\Admin\Orders\AddOrderItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\CancelOrderRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\GetOrderByReferenceRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\GetOrderRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\MarkItemsAsShippedRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\ReturnItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\UpdateItemsRequest;
use Gets\QliroApi\Api\Requests\Admin\Orders\UpdateMerchantReferenceRequest;
use Gets\QliroApi\Api\Responses\Admin\Orders\GetOrderResponse;
use Gets\QliroApi\Dtos\Order\ReturnItemsDto;
use Gets\QliroApi\Dtos\Order\UpdateItemsDto;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Dtos\Order\MarkItemsAsShippedDto;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\BaseResource;
use Saloon\Http\Response;

class OrdersResource extends BaseResource
{
    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     *
     */
    public function addOrderItems(array $orderItemData): Response
    {
        return $this->connector->send(new AddOrderItemsRequest($orderItemData));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function cancelOrder(int $orderId): Response
    {
        return $this->connector->send(new CancelOrderRequest($orderId));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function getOrder(int $orderId): GetOrderResponse
    {
        return GetOrderResponse::fromResponse($this->connector->send(new GetOrderRequest($orderId)));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function getOrderByMerchantReference(string $merchantReference): GetOrderResponse
    {
        return GetOrderResponse::fromResponse($this->connector->send(new GetOrderByReferenceRequest($merchantReference)));
    }

    /**
     * Mark order items as shipped
     *
     * @param MarkItemsAsShippedDto $data Data containing order items to mark as shipped
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function markItemsAsShipped(MarkItemsAsShippedDto $data): Response
    {
        return $this->connector->send(new MarkItemsAsShippedRequest($data->toArray()));
    }

    /**
     * Update order items
     *
     * @param array $data Data containing order items to update
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function updateItems(UpdateItemsDto $data): Response
    {
        return $this->connector->send(new UpdateItemsRequest($data->toArray()));
    }

    /**
     * Return order items
     *
     * @param array $data Data containing order items to return
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function returnItems(ReturnItemsDto $data): Response
    {
        return $this->connector->send(new ReturnItemsRequest($data->toArray()));
    }

    /**
     * Update merchant reference
     *
     * @param array $data Data containing merchant reference to update
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function updateMerchantReference(int $orderId, string $newReference): Response
    {
        return $this->connector->send(new UpdateMerchantReferenceRequest($orderId, $newReference));
    }
}
