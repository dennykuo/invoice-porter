# 擴充新廠商指南

本文件給未來想加入第二家電子發票廠商（綠界 ECPay、歐付寶 O'Pay、紅陽 Pay2Go 等）的貢獻者參考。本套件目前**只實作藍新（NewebPay/EZPay）**，但 namespace 與目錄結構刻意保留多廠商擴充空間。

> 在動工前，請先讀 `docs/ezpay-api-mapping.md`，了解 SDK 如何把藍新文件結構化（Request DTO、Enum、CheckCode 策略）。

## 目錄 layout

新廠商請鏡像 `src/Ezpay/` 結構，namespace 採 `InvoicePorter\<Vendor>\…`。例如綠界：

```
src/
├── Exceptions/
│   └── InvoiceException.php          # 共用 abstract base（已存在）
├── Ezpay/                            # 既有藍新實作（不要動）
│   └── ...
└── Ecpay/                            # 新廠商
    ├── Crypto/
    │   ├── AesCryptor.php            # 各家加密參數不同，獨立實作
    │   └── CheckMacGenerator.php     # 綠界稱 CheckMacValue，演算法不同
    ├── Enums/
    │   ├── InvoiceStatus.php
    │   ├── TaxType.php
    │   └── ...
    ├── Exceptions/
    │   ├── EcpayException.php        # extends InvoiceException
    │   ├── EcpayValidationException.php
    │   ├── EcpayApiException.php
    │   ├── EcpayCheckMacException.php
    │   └── EcpayTransportException.php
    ├── Http/
    │   ├── EcpayHttpClient.php
    │   └── HttpClientFactory.php
    ├── Requests/
    │   ├── EcpayRequest.php          # abstract base
    │   ├── InvoiceIssueRequest.php
    │   └── ...
    ├── Responses/
    │   ├── EcpayResponse.php
    │   └── ...
    ├── Support/
    ├── Environment.php
    ├── EcpayConfig.php
    └── EcpayInvoiceClient.php        # facade
```

對應的測試也鏡像：

```
tests/
├── Unit/<Vendor>/
└── Feature/<Vendor>/Client/
```

## 可以重用什麼

| 來源 | 用途 |
|------|-----|
| PHP 內建 `openssl_encrypt` / `openssl_decrypt` | AES 加解密原語 |
| PHP 內建 `hash('sha256', …)` | 雜湊原語 |
| `guzzlehttp/guzzle` | HTTP 傳輸 |
| `GuzzleHttp\Handler\MockHandler` | Feature test 攔截 HTTP 流量（見 `tests/Feature/Client/ClientTestCase.php`） |
| `InvoicePorter\Exceptions\InvoiceException` | 跨廠商 exception 共用根（必繼承） |
| PHPUnit、PHP-CS-Fixer、PHPStan 設定 | 既有 `phpstan.neon` / `.php-cs-fixer.dist.php` 直接適用 |

## 不要從 Ezpay 抽出共用層

各家發票廠商的 API 差異**非常大**，硬抽共用基底類只會做出錯誤的抽象。請勿做以下動作：

| 禁止項目 | 原因 |
|---------|-----|
| 把 `Ezpay\Crypto\AesCryptor` 改名為 `InvoicePorter\Crypto\AesCryptor` 共用 | 各家 block size、padding、輸出編碼（hex / base64）都不同 |
| 抽出 `Contracts\InvoiceClient` interface 強制各家實作 | 各家欄位、方法粒度差異大，介面只能取最大公因數，犧牲型別表達 |
| 抽出 `Http\BaseHttpClient` | 各家 endpoint 路徑、參數包裝、Mac/CheckCode 計算插入點都不同 |
| 把 `Enums/` 提到頂層 (`InvoicePorter\Enums\InvoiceStatus`) | 各家 enum 值意義不同（例如「狀態 1」在藍新是立即開立，綠界是其他語意），共用 enum 會誤導使用者 |

> 原則：**先把第二家完整跑通**（包含全部端點、全部 enum、加解密、CheckCode、Feature test），**再回頭看哪些抽象真的有重複**，再決定是否往 `InvoicePorter\` 上層抽出。先抽再實作幾乎一定會抽錯方向。

## 各家**獨立實作**清單

下列項目每家廠商都要重寫：

- 加密演算法（AES 模式、padding、編碼）
- 簽章 / CheckCode / CheckMacValue 計算邏輯與驗證流程
- 端點 URL（Sandbox / Production base）
- Request 欄位名稱與序列化規則（藍新 `MerchantID_` + `PostData_`，其他廠商可能完全不同）
- Response 欄位解析與錯誤碼對映
- 所有 Enum（即使中文名稱看起來一樣，實際值經常不同）

## Exception 階層約定

**必須**繼承共用根 `InvoicePorter\Exceptions\InvoiceException`：

```php
namespace InvoicePorter\Ecpay\Exceptions;

use InvoicePorter\Exceptions\InvoiceException;

abstract class EcpayException extends InvoiceException
{
}
```

具體子類別建議鏡像藍新四分類（呼叫端會習慣這個粒度）：

```
EcpayException (abstract, extends InvoiceException)
├── EcpayValidationException     # DTO constructor 驗證失敗
├── EcpayApiException            # 廠商業務錯誤碼
├── EcpayCheckMacException       # 簽章 / CheckMac 驗證失敗（命名跟著該廠商術語走）
└── EcpayTransportException      # 網路、5xx、JSON 解析失敗
```

呼叫端可一次 catch 全部廠商的錯誤：

```php
use InvoicePorter\Exceptions\InvoiceException;

try {
    $response = $client->issue($request);
} catch (InvoiceException $e) {
    // 跨廠商一網打盡
}
```

## 測試慣例

- **Unit 層**：純函式 / 邏輯（Crypto、Enum、Request DTO 驗證、Config）。不打網路。
- **Feature/Client 層**：用 Guzzle `MockHandler` 攔截 request，**對 PostData_ 解密**後驗證 payload，再回傳 mock response 驗證 client 解析。可參考 `tests/Feature/Client/ClientTestCase.php`、`ClientIssueTest.php`。
- 錯誤碼對映用獨立檔（`ErrorMappingTest`），方便文件升版時補新錯誤碼。

## Enum 命名慣例

- 命名空間：`InvoicePorter\<Vendor>\Enums\<Domain>`
- 域名（Domain）盡量沿用該廠商文件用詞，避免硬翻譯（例如綠界叫 `InvoiceState` 就用 `InvoiceState`，不要為了和藍新一致硬改成 `InvoiceStatus`）
- 同名 Enum 在不同 vendor 完全合法，因為它們在不同 namespace

## 文件貢獻

新廠商需附：

- `docs/<vendor>-api-mapping.md`（鏡像 `docs/ezpay-api-mapping.md` 結構）
- README 中加入新廠商的「快速開始」段落
- `composer.json` 的 `keywords` 視情況追加（只加**已實作**的關鍵字，避免 Packagist 搜尋誤導）

## CI 期望

PR 必須通過：

```bash
composer ci  # cs-check + phpstan level 10 + phpunit 全綠
```

PHPStan level 10 對 generic / readonly / strict 型別要求嚴格，請在送 PR 前本地跑過。
