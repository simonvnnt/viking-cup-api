<?php

namespace App\Dto;

class BilletwebTicketDto
{
    public function __construct(
        public string              $id,
        public string              $extId,
        public string              $barcode,
        public bool              $used,
        public ?string             $lane,
        public ?string             $usedDate,
        public string              $email,
        public string              $firstname,
        public string              $name,
        public string              $ticket,
        public string              $category,
        public string              $ticketId,
        public float               $price,
        public ?string             $seatingLocation,
        public string              $lastUpdate,
        public ?string             $reductionCode,
        public ?string             $authorizationCode,
        public string              $pass,
        public bool                $disabled,
        public string              $productManagement,
        public string              $productDownload,
        public string              $orderId,
        public string              $orderExtId,
        public string              $orderFirstname,
        public string              $orderName,
        public string              $orderEmail,
        public string              $orderDate,
        public bool              $orderPaid,
        public string              $orderPaymentType,
        public string              $orderPaymentDate,
        public string              $orderOrigin,
        public float               $orderPrice,
        public int                 $orderSession,
        public ?string             $sessionStart,
        public bool              $orderAccreditation,
        public string              $orderManagement,
        public string              $orderLanguage,
        public ?array              $custom = null
    )
    {}
}