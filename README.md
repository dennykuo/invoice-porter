# Invoice Porter — 藍新電子發票 PHP SDK

[![CI](https://github.com/dennykuo/invoice-porter/actions/workflows/ci.yml/badge.svg)](https://github.com/dennykuo/invoice-porter/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/dennykuo/invoice-porter.svg)](https://packagist.org/packages/dennykuo/invoice-porter)
[![PHP Version](https://img.shields.io/packagist/dependency-v/dennykuo/invoice-porter/php.svg)](https://packagist.org/packages/dennykuo/invoice-porter)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

藍新（NewebPay/EZPay）電子發票 API 的 PHP SDK。覆蓋 EZP_INVI_1.2.2（2024/4/22）所有發票 / 折讓相關端點，以及 EZP_Track_1.0.0（2018/10/3）所有字軌管理端點，並以 type-safe 的 DTO + Enum 取代字串魔術值。

## 特色

- **發票 7 端點**：開立、觸發開立、作廢、查詢、開立折讓、觸發/取消折讓、作廢折讓
- **字軌 3 端點**：新增字軌、字軌資料管理、字軌資料查詢
- 18 支 backed Enum 取代字串魔術值
- AES-256-CBC 加解密、CheckCode 驗證皆獨立模組可測試
- 例外階層化：`Validation` / `Api` / `CheckCode` / `Transport`
- HTTP 層注入點完整，可用 Guzzle MockHandler 做 feature test
- PHPStan level 10、PHP-CS-Fixer（PSR-12 + `declare_strict_types`）、PHP 8.1 / 8.2 / 8.3 CI

## 系統需求

- PHP **8.1+**（內建 `ext-openssl`、`ext-json`）
- `guzzlehttp/guzzle` ^7.5

## 安裝

```bash
composer require dennykuo/invoice-porter
```

## 設定

`EzpayConfig` 為唯一入口，全部欄位皆 readonly：

```php
use InvoicePorter\Ezpay\Environment;
use InvoicePorter\Ezpay\EzpayConfig;

new EzpayConfig(
    merchantId: 'YOUR_MERCHANT_ID',
    hashKey: 'YOUR_HASH_KEY_32_CHARS_xxxxxxxxxxx',  // 必須 32 字元
    hashIv: 'YOUR_IV_16_CHARS',                     // 必須 16 字元
    environment: Environment::Sandbox,              // 或 Environment::Production
    timeoutSeconds: 10.0,                           // 預設 10 秒
    connectTimeoutSeconds: 5.0,                     // 預設 5 秒
);
```

也可改從環境變數讀取：

```php
EzpayConfig::fromEnv();              // 讀 EZPAY_MERCHANT_ID / HASH_KEY / HASH_IV / ENVIRONMENT
EzpayConfig::fromEnv('VENDOR_A_');   // 讀 VENDOR_A_MERCHANT_ID …（適合一個系統多個藍新帳號）
```

`EZPAY_ENVIRONMENT` 接受 `sandbox`（預設）或 `production`。

## 快速開始

```php
use InvoicePorter\Ezpay\EzpayInvoiceClient;
use InvoicePorter\Ezpay\Enums\Category;
use InvoicePorter\Ezpay\Enums\InvoiceStatus;
use InvoicePorter\Ezpay\Enums\TaxType;
use InvoicePorter\Ezpay\Requests\InvoiceIssueRequest;
use InvoicePorter\Ezpay\Requests\Items\InvoiceItem;

$client = new EzpayInvoiceClient(EzpayConfig::fromEnv());

$response = $client->issue(new InvoiceIssueRequest(
    status: InvoiceStatus::Immediate,
    merchantOrderNo: 'ORD-' . date('YmdHis'),
    category: Category::B2C,
    taxType: TaxType::Taxable,
    amount: 476,
    taxAmount: 24,
    totalAmount: 500,
    items: [
        new InvoiceItem(name: '商品一', count: 1, unit: '個', price: 500, amount: 500),
    ],
));

echo $response->invoiceNumber();
```

## API 對照

| 中文 | 方法 | 端點 | Version |
|------|------|------|---------|
| 開立發票 | `issue()` | `invoice_issue` | 1.5 |
| 觸發開立發票 | `touchIssue()` | `invoice_touch_issue` | 1.0 |
| 作廢發票 | `invalid()` | `invoice_invalid` | 1.0 |
| 查詢發票 | `search()` / `searchRedirectHtml()` | `invoice_search` | 1.3 |
| 開立折讓 | `issueAllowance()` | `allowance_issue` | 1.3 |
| 觸發/取消折讓 | `touchAllowance()` | `allowance_touch_issue` | 1.0 |
| 作廢折讓 | `invalidAllowance()` | `allowanceInvalid` | 1.0 |

## 字軌管理 API（EZP_Track_1.0.0）

藍新「電子發票字軌管理」屬會員（公司）層級 API，與發票 API 用不同的金鑰與參數包裝（envelope 第一欄為 `CompanyID_` 而非 `MerchantID_`）。`EzpayConfig` 沿用同一個入口，nullable `companyId` / `companyHashKey` / `companyHashIv` 三欄需於使用字軌時提供。

| 中文 | 方法 | 端點 | Version |
|------|------|------|---------|
| 新增字軌 | `trackCreate()` | `Api_number_management/createNumber` | 1.0 |
| 字軌資料管理 | `trackManage()` | `Api_number_management/manageNumber` | 1.0 |
| 字軌資料查詢 | `trackSearch()` | `Api_number_management/searchNumber` | 1.0 |

```php
use InvoicePorter\Ezpay\Enums\InvoiceTerm;
use InvoicePorter\Ezpay\EzpayConfig;
use InvoicePorter\Ezpay\EzpayTrackClient;
use InvoicePorter\Ezpay\Requests\Track\TrackCreateRequest;

$config = new EzpayConfig(
    merchantId: 'YOUR_MERCHANT_ID',
    hashKey: 'YOUR_HASH_KEY_32_CHARS_xxxxxxxxxxx',
    hashIv: 'YOUR_IV_16_CHARS',
    companyId: 'YOUR_COMPANY_ID',                              // 字軌專用
    companyHashKey: 'YOUR_COMPANY_HASH_KEY_32_xxxxxxxxxxxxx',  // 字軌專用
    companyHashIv: 'YOUR_COMPANY_IV',                          // 字軌專用
);

$client = new EzpayTrackClient($config);

$response = $client->trackCreate(new TrackCreateRequest(
    year: '115',                // 民國年三碼
    term: InvoiceTerm::JanFeb,  // 1=一二月、2=三四月、…
    aphabeticLetter: 'AB',      // 字軌字母（兩碼大寫）
    startNumber: '00000000',    // 起號 8 碼
    endNumber: '00000049',      // 訖號 8 碼
));

echo $response->managementNo();  // 新增成功後的字軌管理編號
```

> 字軌 API 詳細欄位、CheckCode 策略與錯誤碼對照請見 [`docs/ezpay-track-api-mapping.md`](docs/ezpay-track-api-mapping.md)。

## 使用範例

`examples/01-issue.php` … `examples/08-search-redirect.php` 已涵蓋全部端點。下列為各方法的最小呼叫片段，省略 `use` 行；類別都在 `InvoicePorter\Ezpay\Requests\…`、`InvoicePorter\Ezpay\Enums\…`、`InvoicePorter\Ezpay\Requests\Items\…`。

### 觸發開立 / 作廢 / 折讓系列

```php
// 觸發開立（先以 InvoiceStatus::Pending 建單後，呼叫此 API 才實際開立）
$client->touchIssue(new InvoiceTouchIssueRequest(
    merchantOrderNo: 'ORD-20260504-001',
    totalAmount: 500,
));

// 作廢發票（最小欄位）
$client->invalid(new InvoiceInvalidRequest(
    invoiceNumber: 'AA00000076',
    invalidReason: '訂單取消',
));

// 開立折讓
$client->issueAllowance(new AllowanceIssueRequest(
    invoiceNo: 'AA00000076',
    merchantOrderNo: 'ORD-20260504-001',
    totalAmount: 100,
    taxAmount: 5,
    items: [new AllowanceItem(name: '商品一', count: 1, unit: '個', price: 95, amount: 95, taxAmount: 5)],
));

// 確認 / 取消折讓
$client->touchAllowance(new AllowanceTouchIssueRequest(
    allowanceNo: 'A001',
    status: AllowanceTouchStatus::Confirm,  // 或 ::Deny（取消）
));

// 作廢折讓
$client->invalidAllowance(new AllowanceInvalidRequest(
    allowanceNo: 'A001',
    invalidReason: '客戶取消',
));
```

### 查詢發票

```php
// 用發票號碼查詢（必須帶 randomNum）
$response = $client->search(new InvoiceSearchRequest(
    searchType: SearchType::ByInvoiceNumber,
    merchantOrderNo: 'ORD-20260504-001',
    invoiceNumber: 'AA00000076',
    randomNum: '0991',
));

echo $response->lifecycleStatus()?->value;  // 1=已開立、2=已作廢
```

### 進階情境

<details>
<summary><b>B2B 發票（買方統編必填）</b></summary>

```php
new InvoiceIssueRequest(
    status: InvoiceStatus::Immediate,
    merchantOrderNo: 'ORD-...',
    category: Category::B2B,
    taxType: TaxType::Taxable,
    amount: 476, taxAmount: 24, totalAmount: 500,
    buyerName: '王大公司',
    buyerUbn: '12345678',  // B2B 必填，否則 EzpayValidationException
    items: [new InvoiceItem(...)],
);
```
</details>

<details>
<summary><b>載具（手機條碼 / 自然人憑證 / 會員）</b></summary>

```php
new InvoiceIssueRequest(
    // ...
    carrierType: CarrierType::Mobile,    // 或 ::CitizenDigitalCertificate / ::Member
    carrierNum: '/ABC1234',              // 手機條碼以 / 開頭
);
```
</details>

<details>
<summary><b>愛心捐贈碼</b></summary>

```php
new InvoiceIssueRequest(
    // ...
    loveCode: '13994',  // 不可與 carrierType 同時使用
);
```
</details>

<details>
<summary><b>延遲開立（Status=3）</b></summary>

```php
new InvoiceIssueRequest(
    status: InvoiceStatus::Scheduled,
    createStatusTime: '2026-06-01',  // YYYY-MM-DD，必填
    // ...
);
```

之後可用 `touchIssue()` 提前觸發，或交給藍新到期自動開立。
</details>

<details>
<summary><b>混合稅率（TaxType=9）</b></summary>

```php
// 混合稅率時，每個 item 必須給 taxType（'1' 應稅、'2' 零稅、'3' 免稅）
new InvoiceIssueRequest(
    taxType: TaxType::Mixed,
    items: [
        new InvoiceItem(name: '應稅商品', count: 1, unit: '個', price: 100, amount: 100, taxType: '1'),
        new InvoiceItem(name: '免稅商品', count: 1, unit: '個', price: 50,  amount: 50,  taxType: '3'),
    ],
    // ...
);
```
</details>

## Response 通用方法

所有 Response 物件繼承 `EzpayResponse`，提供以下共用方法：

```php
$response->isSuccess();    // bool — 等同 status() === 'SUCCESS'
$response->status();       // string — 'SUCCESS' 或業務錯誤碼
$response->message();      // string — 藍新原始訊息
$response->rawResponse();  // array  — 完整 envelope，方便寫 log
```

特定端點的回傳欄位請見 `src/Ezpay/Responses/*.php`，例如 `InvoiceIssueResponse::invoiceNumber()` / `invoiceTransNo()` / `randomNum()` / `barcode()` / `qrcodeL()` / `qrcodeR()` 等。

## 錯誤處理

所有錯誤都會丟 exception，請統一 `try/catch`：

| Exception | 情境 |
|-----------|------|
| `EzpayValidationException` | DTO 內欄位驗證失敗（發生於 constructor） |
| `EzpayApiException` | 藍新回業務錯誤碼（例 `KEY10002`、`INV10003`） |
| `EzpayCheckCodeException` | CheckCode 驗證不通過或欄位不齊 |
| `EzpayTransportException` | HTTP 連線、5xx、JSON 解析失敗 |

所有 exception 皆繼承 `EzpayException`（abstract，繼承 `RuntimeException`），可一次 catch。

```php
use InvoicePorter\Ezpay\Exceptions\EzpayApiException;
use InvoicePorter\Ezpay\Exceptions\EzpayException;

try {
    $response = $client->issue($request);
} catch (EzpayApiException $e) {
    // 業務錯誤：可拿到 errorCode / message / rawResponse
    log_business_error($e->errorCode, $e->getMessage());
} catch (EzpayException $e) {
    // 其餘 SDK 錯誤
    log_sdk_error($e);
}
```

## CheckCode 驗證

文件附件二記載 5 欄參與 SHA256：`InvoiceTransNo`、`MerchantID`、`MerchantOrderNo`、`RandomNum`、`TotalAmt`。但藍新實際上對「作廢發票」「折讓系列」回應未必提供完整 5 欄，因此本 SDK 採取保守策略：

- **預設驗證**：`invoice_issue`、`invoice_touch_issue`、`invoice_search`
- **作廢發票** (`invoice_invalid`)：使用者建構 Request 時若帶齊 `randomNum` / `invoiceTransNo` / `merchantOrderNo` / `totalAmount`，才會驗 CheckCode
- **折讓三組** (`allowance_*`)：預設不驗，可透過建構參數 `expectCheckCode: true` 明確開啟

```php
// 作廢發票 — 帶齊 4 個欄位即啟用 CheckCode 驗證
$client->invalid(new InvoiceInvalidRequest(
    invoiceNumber: 'AA00000076',
    invalidReason: '訂單取消',
    randomNum: '0991',
    invoiceTransNo: '24050414461511234',
    merchantOrderNo: 'ORD-20260504-001',
    totalAmount: 500,
));

// 折讓系列 — 明確開啟驗證
$client->issueAllowance(new AllowanceIssueRequest(
    // ...
    expectCheckCode: true,
));
```

## 查詢發票轉址

藍新 v1.2.2 為 `DisplayFlag` 提供兩種模式：

- `DisplayFlag::Redirect`（=1）：呼叫 `searchRedirectHtml()` 取回自動 submit 的 HTML 字串，使用者自行 `echo`：

  ```php
  echo $client->searchRedirectHtml($request);
  ```

- `DisplayFlag::ResultUrl`（=2，v1.2.2 新增）：呼叫 `search()` 走一般 API 流程，從 Response 取出查詢結果 URL：

  ```php
  $response = $client->search($request);
  $url = $response->searchResultUrl();
  ```

## 範例程式

請見 `examples/` 目錄。使用前先：

```bash
cp examples/.env.example examples/.env
# 編輯 examples/.env 填入您自己的測試憑證
php examples/01-issue.php
```

## 測試

```bash
composer install
composer test          # phpunit
composer test-coverage # phpunit + clover + html report
composer stan          # phpstan level 10
composer cs-check      # php-cs-fixer dry-run
composer ci            # cs-check + stan + test
```

> 本機若以高於 composer.json `require.php` 最低版本（`^8.1`）的 PHP 跑 `cs-check`，PHP-CS-Fixer 會在 stderr 印一行版本不符提醒，**不影響結果且 exit code 為 0**，可忽略。CI matrix 會在 PHP 8.1 / 8.2 / 8.3 / 8.4 各跑一次。

## 文件

- [`CHANGELOG.md`](CHANGELOG.md) — 版本歷程
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — 貢獻指南
- [`SECURITY.md`](SECURITY.md) — 安全回報政策
- [`docs/ezpay-api-mapping.md`](docs/ezpay-api-mapping.md) — 藍新 EZP_INVI_1.2.2 發票文件 vs SDK 對照表（升版用）
- [`docs/ezpay-track-api-mapping.md`](docs/ezpay-track-api-mapping.md) — 藍新 EZP_Track_1.0.0 字軌文件 vs SDK 對照表
- [`docs/extending.md`](docs/extending.md) — 擴充新廠商指南（給未來貢獻者）

## Roadmap

目前實作藍新 EZPay 一家。namespace 採 `InvoicePorter\<Vendor>\…` 結構，未來歡迎以 PR 形式擴充其他電子發票服務商（綠界 ECPay、歐付寶 O'Pay、紅陽 Pay2Go 等）。新廠商擴充指南請見 [`docs/extending.md`](docs/extending.md)。

跨廠商錯誤可一次 catch 共用根 `InvoicePorter\Exceptions\InvoiceException`：

```php
use InvoicePorter\Exceptions\InvoiceException;

try {
    $response = $client->issue($request);
} catch (InvoiceException $e) {
    // 不論藍新或未來其他廠商，這裡都會接到
}
```

## License

MIT.
