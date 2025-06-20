<?php

namespace Gets\QliroApi\Api\Resources\Merchant;

use Gets\QliroApi\Api\QliroConnector;
use Gets\QliroApi\Api\Requests\Merchant\Orders\GetOrderRequest;
use Gets\QliroApi\Api\Requests\Merchant\Orders\GetOrderByMerchantReferenceRequest;
use Gets\QliroApi\Api\Requests\Merchant\Orders\CreateOrderRequest;
use Gets\QliroApi\Api\Requests\Merchant\Orders\UpdateOrderRequest;
use Gets\QliroApi\Api\Responses\Merchant\Orders\GetOrderResponse;
use Gets\QliroApi\Exceptions\QliroException;
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
     */
    public function getOrder(string $orderId): GetOrderResponse
    {
        return GetOrderResponse::fromResponse(
            $this->connector->send(new GetOrderRequest($orderId))
        );
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function getOrderByMerchantReference(string $merchantReference): GetOrderResponse
    {
        return GetOrderResponse::fromResponse(
            $this->connector->send(new GetOrderByMerchantReferenceRequest($merchantReference))
        );
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function createOrder(array $orderData): Response
    {
        return $this->connector->send(new CreateOrderRequest($orderData));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws QliroException When API returns an error response
     */
    public function updateOrder(string $orderId, array $orderData): Response
    {
        return $this->connector->send(new UpdateOrderRequest($orderId, $orderData));
    }
}
