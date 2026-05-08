<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Support;

final class RedirectFormBuilder
{
    public static function build(string $actionUrl, string $merchantId, string $postData): string
    {
        $action = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
        $mid = htmlspecialchars($merchantId, ENT_QUOTES, 'UTF-8');
        $payload = htmlspecialchars($postData, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting to EZPay</title>
</head>
<body>
    <form id="ezpay-redirect-form" method="post" action="{$action}">
        <input type="hidden" name="MerchantID_" value="{$mid}">
        <input type="hidden" name="PostData_" value="{$payload}">
        <noscript>
            <button type="submit">Continue to EZPay</button>
        </noscript>
    </form>
    <script>document.getElementById('ezpay-redirect-form').submit();</script>
</body>
</html>
HTML;
    }
}
