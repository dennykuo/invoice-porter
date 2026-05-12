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

或從 array 建構（Laravel `config:cache` 友善）：

```php
// config/ezpay.php
return [
    'merchant_id'             => env('EZPAY_MERCHANT_ID'),
    'hash_key'                => env('EZPAY_HASH_KEY'),
    'hash_iv'                 => env('EZPAY_HASH_IV'),
    'environment'             => env('EZPAY_ENVIRONMENT', 'sandbox'),
    'timeout_seconds'         => 10.0,
    'connect_timeout_seconds' => 5.0,
    // 以下三欄為字軌 API 才需要
    'company_id'              => env('EZPAY_COMPANY_ID'),
    'company_hash_key'        => env('EZPAY_COMPANY_HASH_KEY'),
    'company_hash_iv'         => env('EZPAY_COMPANY_HASH_IV'),
];

// AppServiceProvider 之類
$config = EzpayConfig::fromArray(config('ezpay'));
```

`fromArray()` 只接受 snake_case keys（對齊 Laravel `config/*.php` 慣例）；unknown keys 會被忽略以保留使用者擴充自家欄位的空間（例如 `'logging' => true`）。注意 `fromArray()` 不會 fallback 讀環境變數 — 想用 env 請改用 `fromEnv()`。對 `php artisan config:cache` 後 `getenv()` 失效的情境，這是最直觀的解法。

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
    merchantOrderNo: 'ORD_' . date('YmdHis'),
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

> **⚠️ `merchantOrderNo` 規則**（自 0.4.1 起在 SDK 層強制）— 藍新規格 Varchar(20)，**僅允許英文、數字、底線**。常見的 `'ORD-20260504-001'` 含連字號（`-`）會被 SDK 直接拋 `EzpayValidationException`，請改用底線 `_` 作分隔（例：`'ORD_20260504_001'`）。完整欄位規格與 SDK 驗證對照見 [`docs/ezpay-api-mapping.md`](docs/ezpay-api-mapping.md#欄位規格-vs-sdk-驗證-cheatsheet)。
>
> **⚠️ `printFlag` 自 0.5.0 起為必填** — 是否寄送紙本是業務語意決策，過去預設 `PrintFlag::No` 配合 B2C 又強制要求載具/捐贈碼，等於是讓最少參數呼叫的使用者直接掉坑。請依場景明確指定 `PrintFlag::Yes`（寄紙本）或 `PrintFlag::No`（不寄紙本，需配 carrier 或 loveCode）。

## API 對照

| 中文 | 方法 | 端點 | Version |
|------|------|------|---------|
| 開立發票 | `issue()` | `invoice_issue` | 1.5 |
| 觸發開立發票 | `touchIssue()` | `invoice_touch_issue` | 1.0 |
| 作廢發票 | `invalid()` | `invoice_invalid` | 1.0 |
| 查詢發票 | `search()` / `searchRedirectHtml()` | `invoice_search` | 1.3 |
| 開立後跳查詢頁 | `publicQueryRedirectHtml()` | `invoice_search`（Redirect 模式） | 1.3 |
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
    merchantOrderNo: 'ORD_20260504_001',
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
    merchantOrderNo: 'ORD_20260504_001',
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
    merchantOrderNo: 'ORD_20260504_001',
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
    merchantOrderNo: 'ORD_...',
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

> **提醒** — B2C 且 `PrintFlag::No`（不索取紙本）時，藍新會要求 `carrierType + carrierNum` 或 `loveCode` 擇一；SDK 自 0.4.0 起會在 `new InvoiceIssueRequest(...)` 直接以 `EzpayValidationException` 提早攔下，省一輪藍新後端來回。**自 0.5.0 起本檢查涵蓋所有 TaxType**（`Taxable` / `ZeroRate` / `Exempt` / `Mixed`），並加入更多 cross-field invariants（載具與捐贈碼互斥、B2B 不可使用載具或捐贈碼、carrierType / carrierNum 必須成對提供）。完整規則見 [`docs/ezpay-api-mapping.md`](docs/ezpay-api-mapping.md#跨欄位-invariants)。

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

時間欄位除了既有 `createTime(): ?string`（原始字串）外，自 0.4.0 起亦提供原生型別版：

```php
$response->createTime();    // ?string — 藍新原始 'Y-m-d H:i:s'
$response->createTimeAt();  // ?DateTimeImmutable — 解析後物件，省去自行 parse
```

`createTimeAt()` 涵蓋 `InvoiceIssueResponse` / `InvoiceSearchResponse` / `InvoiceInvalidResponse` / `AllowanceIssueResponse` / `AllowanceTouchIssueResponse`；`AllowanceInvalidResponse` 對應為 `invalidTimeAt()`（與既有 `invalidTime()` 對齊）。皆使用 PHP `date_default_timezone_get()` 之預設時區，需要 `Asia/Taipei` 請呼叫端自行 `->setTimezone(new DateTimeZone('Asia/Taipei'))`；解析失敗或欄位缺則回 null（不丟例外）。

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

### 錯誤碼語意分群（0.4.1+）

藍新錯誤碼以前綴分群，呼叫端常見處理策略不同：

```php
try {
    $response = $client->issue($request);
} catch (EzpayApiException $e) {
    if ($e->isDuplicateOrderNo()) {
        // NOR10001 / LIB10003 → 產生新訂單編號後重新建 Request 重試
        return retry_with_new_order_no();
    }
    if ($e->isAuthError()) {
        // INV900xx / KEY100xx → 憑證或解密問題，告警 ops 修設定
        alert_ops($e);
        throw $e;
    }
    if ($e->isFieldFormatError()) {
        // INV100xx / INV700xx → 欄位格式錯，引導使用者修正
        return show_validation_error($e);
    }
    throw $e;
}
```

| Helper | 涵蓋前綴 / 碼 | 建議處理 |
|--------|--------------|---------|
| `isFieldFormatError()` | `INV100xx` / `INV700xx` | 引導使用者修正輸入；重試無意義 |
| `isAuthError()` | `INV900xx` / `KEY100xx` | 告警 ops 檢查 hashKey / hashIv 設定 |
| `isDuplicateOrderNo()` | `NOR10001` / `LIB10003` | 產生新訂單編號重試 |
| `errorCodePrefix()` | （任意，未匹配時回空字串） | 自行分群或記 log 用 |

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
    merchantOrderNo: 'ORD_20260504_001',
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

### 開立後快速產生公開查詢頁轉址

很多時候要做的就是「issue 完發票後馬上跳到藍新公開查詢頁讓使用者看明細」，自 0.4.0 起 SDK 提供兩種 sugar 寫法（內部都會包成 `SearchType::ByInvoiceNumber` + `DisplayFlag::Redirect` 的 `InvoiceSearchRequest` 後委派給 `searchRedirectHtml()`，回傳自動 submit 的 form HTML — 藍新公開查詢頁採 form-post，非 GET URL）：

```php
// A：直接給四個欄位，不必先組 Request
echo $client->publicQueryRedirectHtml(
    invoiceNumber: 'AA00000076',
    randomNum: '0991',
    merchantOrderNo: 'ORD_20260504_001',
    totalAmount: 500,
);

// B：剛 issue 完，直接從 Response 產生 Request 再丟回 searchRedirectHtml()
$response = $client->issue($issueRequest);
echo $client->searchRedirectHtml($response->toSearchRequest());
```

`InvoiceIssueResponse::toSearchRequest()` 在缺 `invoiceNumber` / `randomNum` 時會丟 `EzpayValidationException`（藍新異常回應時才會發生）。

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
