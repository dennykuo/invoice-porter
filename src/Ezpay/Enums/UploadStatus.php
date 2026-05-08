<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

enum UploadStatus: string
{
    case NotUploaded = '0';
    case Uploaded = '1';
    case PendingUpload = '2';
    case UploadFailed = '3';
    case ConsumerUploaded = '4';
}
