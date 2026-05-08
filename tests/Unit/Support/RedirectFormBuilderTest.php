<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Support;

use InvoicePorter\Ezpay\Support\RedirectFormBuilder;
use PHPUnit\Framework\TestCase;

final class RedirectFormBuilderTest extends TestCase
{
    public function testProducesAutoSubmitForm(): void
    {
        $html = RedirectFormBuilder::build('https://cinv.ezpay.com.tw/Api/invoice_search', '00000000', 'abcd1234');

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="https://cinv.ezpay.com.tw/Api/invoice_search"', $html);
        $this->assertStringContainsString('name="MerchantID_" value="00000000"', $html);
        $this->assertStringContainsString('name="PostData_" value="abcd1234"', $html);
        $this->assertStringContainsString('document.getElementById', $html);
    }

    public function testEscapesHtmlInValues(): void
    {
        $html = RedirectFormBuilder::build(
            'https://example.com/x?q=1&z=2',
            '<script>alert(1)</script>',
            'a"b',
        );

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('&quot;', $html);
    }
}
