<?php

namespace Source\Project\DataContainers;

use Source\Base\Core\DataContainer;


/**
 * @TODO переименовать
 */
class OrderStatusesDC extends DataContainer
{
    /**
     * @DESC ALL VAR WITH INFORMATION
     */

    public const array MAPPING_SELECTED_TABS = [
        'new' => 'NEW',
        'pickup' => 'PICKUP',
        'delivery' => 'DELIVERY',
        'sign_required' => 'SIGN_REQUIRED',
        'cargo_assembly' => 'KASPI_DELIVERY_CARGO_ASSEMBLY',
        'wait_for_point_delivery' => 'KASPI_DELIVERY_WAIT_FOR_POINT_DELIVERY',
        'transmitted' => 'KASPI_DELIVERY_TRANSMITTED',
        'wait_for_courier' => 'KASPI_DELIVERY_WAIT_FOR_COURIER',
        'return_request' => 'KASPI_DELIVERY_RETURN_REQUEST'
    ];

    public const array MAPPING_STATUSES = [
        'cancelled' => 'CANCELLED',
        'completed' => 'COMPLETED',
        'returned' => 'RETURNED',
        'credit_termination_process' => 'CREDIT_TERMINATION_PROCESS',
        'cancelled_by_merchant' => 'CANCELLED_BY_MERCHANT'
    ];

    public const array MAPPING_FUNCTION_STATUSES = [
        'activeorders' => 'MAPPING_SELECTED_TABS',
        'archiveorders' => 'MAPPING_STATUSES'
    ];

}