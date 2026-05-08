<?php

declare(strict_types=1);

namespace InvoicePorter\Tests\Unit\Exceptions;

use InvoicePorter\Exceptions\InvoiceException;
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayCheckCodeException;
use InvoicePorter\Ezpay\Exceptions\EzpayException;
use InvoicePorter\Ezpay\Exceptions\EzpayTransportException;
use InvoicePorter\Ezpay\Exceptions\EzpayValidationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class InvoiceExceptionHierarchyTest extends TestCase
{
    public function testInvoiceExceptionIsAbstractRuntimeException(): void
    {
        $reflection = new ReflectionClass(InvoiceException::class);

        $this->assertTrue($reflection->isAbstract(), 'InvoiceException 必須是 abstract');
        $this->assertTrue(
            $reflection->isSubclassOf(RuntimeException::class),
            'InvoiceException 必須繼承 RuntimeException',
        );
    }

    public function testEzpayExceptionInheritsFromInvoiceException(): void
    {
        $reflection = new ReflectionClass(EzpayException::class);

        $this->assertTrue($reflection->isAbstract(), 'EzpayException 必須維持 abstract');
        $this->assertSame(InvoiceException::class, $reflection->getParentClass()?->getName());
    }

    /**
     * @return iterable<string, array{class-string<EzpayException>}>
     */
    public static function concreteEzpayExceptionProvider(): iterable
    {
        yield 'validation' => [EzpayValidationException::class];
        yield 'check-code' => [EzpayCheckCodeException::class];
        yield 'transport' => [EzpayTransportException::class];
    }

    /**
     * @dataProvider concreteEzpayExceptionProvider
     * @param class-string<EzpayException> $exceptionClass
     */
    public function testConcreteEzpayExceptionsBubbleThroughInvoiceException(string $exceptionClass): void
    {
        $exception = new $exceptionClass('boom');

        $this->assertInstanceOf(EzpayException::class, $exception);
        $this->assertInstanceOf(InvoiceException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testEzpayApiExceptionBubblesThroughInvoiceException(): void
    {
        $exception = new EzpayApiException('KEY10002', 'decrypt failed', ['Status' => 'KEY10002']);

        $this->assertInstanceOf(EzpayException::class, $exception);
        $this->assertInstanceOf(InvoiceException::class, $exception);
        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame('KEY10002', $exception->errorCode);
    }
}
