<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Enums;

/**
 * 發票字軌期別（雙月制）。
 *
 * 對應藍新 EZP_Track_1.0.0 文件「Term」欄位：
 * 1=一二月、2=三四月、3=五六月、4=七八月、5=九十月、6=十一十二月
 */
enum InvoiceTerm: string
{
    case JanFeb = '1';
    case MarApr = '2';
    case MayJun = '3';
    case JulAug = '4';
    case SepOct = '5';
    case NovDec = '6';
}
