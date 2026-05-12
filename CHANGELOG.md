# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.1] - 2026-05-12

承接三支 review agent 的共識梳理，把 0.4.1 + 0.5.0 兩波抽取的尾巴處理乾淨：補一個 0.4.1 漏網的 bug、新增兩個 validator 對齊 namespace、error message 一致化、測試 ~150 行重複碼壓縮。

### Fixed

- **`AllowanceIssueRequest::buyerEmail` 漏 80 字長度檢查**（與 `InvoiceIssueRequest` 不一致；過去只查格式不查長度）。改用新的 `BuyerEmailValidator` 後兩處對齊。

### Added

- `Validation\InvalidReasonValidator`：作廢理由統一驗證（Varchar(20)、mb_strlen 計算）。`InvoiceInvalidRequest` / `AllowanceInvalidRequest` 過去各寫一份且驗證順序不一致，現統一改用此 validator。
- `Validation\BuyerEmailValidator`：買方 email 格式 + 80 字長度。`InvoiceIssueRequest` / `AllowanceIssueRequest` 改用此 validator。
- `MerchantOrderNoValidator::PATTERN` 從 `private` 改 `public`，呼叫端可參照同一規則做 UI 端前置檢查。
- `MerchantOrderNoValidator::summarizeInvalidChars` 強化：違規字元以「,」分隔，空白以 `<space>`、tab 以 `<tab>` 可視化呈現。
- `tests/Unit/Validation/InvalidReasonValidatorTest`、`tests/Unit/Validation/BuyerEmailValidatorTest`：各 5–6 支新測試。
- `AllowanceIssueRequestTest::testRejectsBuyerEmailOver80`：固化 0.5.1 修補的 bug。
- 多個 cross-field 正例補 round-trip assertion（驗證 `toEncryptablePayload()` 真的反映輸入，而非只驗 property 賦值）。

### Changed

- `InvoiceIssueRequest::__construct` 內部重構：
  - `amount`/`taxAmount`/`totalAmount` 三合一錯誤訊息拆三條，且用 SDK 視角的 camelCase（與 `AllowanceIssueRequest` 對齊）。
  - 「B2B 必須提供 buyerUbn」從主 constructor 搬到 `assertCrossField()`，與其他 B2B 規則放一起。
- `assertCrossField` error message 統一 camelCase（過去混用 PascalCase/camelCase）：
  - `CarrierType 與 CarrierNum` → `carrierType 與 carrierNum`
  - `（CarrierType + CarrierNum）與捐贈碼（LoveCode）互斥` → `（carrierType + carrierNum）與 loveCode 互斥`
  - `B2C 不索取紙本（PrintFlag=N）...` → `B2C + printFlag=N ...`
  - `B2B 發票不可使用載具` → `B2B 發票不可使用載具（carrierType / carrierNum）`
  - `B2B 發票不可使用捐贈碼` → `B2B 發票不可使用 loveCode`
- `EzpayApiException::isFieldFormatError()` / `isAuthError()` 內 inline prefix list 提升為 `private const FIELD_FORMAT_ERROR_PREFIXES` / `AUTH_ERROR_PREFIXES`，與既有 `DUPLICATE_ORDER_NO_CODES` 風格一致。
- `Validation\InvoiceItemFieldValidator` API 重設計：
  - 對外 method 改為 `assertInvoiceItemName()` / `assertInvoiceItemUnit()` / `assertAllowanceItemName()` / `assertAllowanceItemUnit()`（對應「具體欄位」）。
  - 過去 `assertName($name, $field)` 收 magic string label，現由 validator 負責 message 字面，呼叫端不必傳 label，減少漂移風險。
- `InvoiceIssueRequest::BUYER_EMAIL_MAX_LENGTH` 標 `@deprecated`，0.6.0 將移除；呼叫端請改引用 `BuyerEmailValidator::MAX_LENGTH`。
- `tests/Unit/Requests/InvoiceIssueRequestTest`：P0 區塊（14 個 case）改用 `validBaseArgs()` + `defaultItem()` helper + spread，砍 ~150 行重複碼。
- `tests/Unit/Requests/InvoiceIssueRequestTest`：寬鬆的 `expectExceptionMessage('B2B')` / `('B2C')` 精準化為實際 error message 子字串，避免與「B2B 發票必須提供 buyerUbn」silent 誤判。

### Internal

- `InvoiceIssueRequest::assertCrossField` docblock 簡化：把 BC change 細節移到 CHANGELOG，docblock 只留 invariants 本身。

## [0.5.0] - 2026-05-12

承接第一位整合者另一份回饋：**規格上有但 SDK 沒擋的「跨欄位 invariants」**。這次踩雷的根因是 `PrintFlag` 預設 `No` 配合 B2C 強制要求載具 / 捐贈碼 — SDK 全程不擋，要等到藍新回 API error 才知道掉坑。本版本把官方手冊裡所有「擇一」「互斥」「同時提供」的硬約束都搬進 `InvoiceIssueRequest::__construct`，讓 SDK 真正當第一道防線。

附帶硬 BC break：`PrintFlag` 預設值移除改必填 — 是否寄送紙本是業務語意決策，預設它就會掩蓋使用者該做的選擇。

### Changed (BC break)

- **`InvoiceIssueRequest::printFlag` 改必填**（拿掉預設 `PrintFlag::No`）。
  - 過去版本最少參數呼叫會隱式套用 `PrintFlag::No`，再因 B2C+PrintFlag=N 要求 carrier/loveCode 而踩雷。改必填強迫呼叫端面對這個業務決策。
  - 參數位置從 constructor 第 9 個（可選區）移動到第 9 個（必填區末）。所有 codebase 用 named arg 呼叫不受 positional 影響，**只有沒帶的呼叫端**會 `ArgumentCountError`。
- **`InvoiceIssueRequest` B2C + PrintFlag=N 的 carrier/loveCode 攔截擴及所有 TaxType**（過去 0.4.0 起只擋 `Taxable`）。error message 由「B2C 應稅發票且 PrintFlag=N」改為「B2C 不索取紙本（PrintFlag=N）」，移除「應稅」描述。
- **`InvoiceIssueRequest` carrier pair 改為 explicit error**：`carrierType` 與 `carrierNum` 必須同時提供或同時省略。過去 carrierType 設了 carrierNum 為空時會 silent treat as missing，現直接拋「CarrierType 與 CarrierNum 必須同時提供或同時省略」。

### Added

- **`InvoiceIssueRequest` 跨欄位 invariants** — 新增 `assertCrossField()` private static 集中四條規則：
  - B2C + PrintFlag=N → 必須提供載具或捐贈碼擇一（全 TaxType）
  - 載具 + 捐贈碼互斥
  - B2B 不可使用載具
  - B2B 不可使用捐贈碼
- 8 支新測試覆蓋所有 cross-field 路徑（含 carrier pair、Mixed/ZeroRate TaxType、B2C+PrintFlag=Y 正例）。

### Migration notes

呼叫端兩種改動可能要做：

1. **沒帶 `printFlag` 的呼叫**：補一個 named arg，依場景選 `PrintFlag::Yes`（紙本寄送）或 `PrintFlag::No`（不索取紙本，需配 carrier 或 loveCode）。
   ```php
   // 0.4.x
   new InvoiceIssueRequest(
       status: InvoiceStatus::Immediate,
       merchantOrderNo: 'ORD_20260512_001',
       category: Category::B2C,
       // ...
   ); // 隱式套用 PrintFlag::No → 沒 carrier/loveCode 會踩雷

   // 0.5.0
   new InvoiceIssueRequest(
       status: InvoiceStatus::Immediate,
       merchantOrderNo: 'ORD_20260512_001',
       category: Category::B2C,
       // ...
       printFlag: PrintFlag::Yes, // ← 必填
   );
   ```

2. **過去送錯但藍新後端才打回的呼叫**（B2B+Carrier、B2B+LoveCode、Carrier+LoveCode 同時送、carrierType 設了 carrierNum 空）：現在在 `new InvoiceIssueRequest(...)` 階段就拋 `EzpayValidationException`。原本就會被藍新打回，SDK 改為提早攔下，呼叫端應修正資料而非繞過驗證。

3. **`searchRedirectExit()` 已於 0.4.0 移除**，仍在用的請改 `searchRedirectHtml()` 或 `publicQueryRedirectHtml()`。

## [0.4.1] - 2026-05-12

第一位整合者使用後回報的欄位驗證缺口：補上**藍新規格本來就有但 SDK 沒擋**的長度、字元集、格式規則，並修正 `merchantOrderNo` 長度寫死的錯誤值（30 → 20，規格為 Varchar(20)）。同時補語意化的錯誤碼分群 helper，呼叫端不必再對個別字串做 match。

### Fixed

- **`MerchantOrderNo` 長度規格修正為 20**（原為 30，是漏看規格的錯誤值）。21–30 字元過去會被 SDK 放行，到藍新後端才被打回 `INV70001` / `INV10014`，屬「假驗證」最糟形式。
- 新增 **`MerchantOrderNo` 字元集檢查**（藍新規格僅允許英文、數字、底線）。違規時 error message 會直接點出違規字元，省一輪 round-trip。
- **`InvoiceItem` / `AllowanceItem` 的 `name`、`unit` 含 `|` 現會被擋**。過去藍新會把單一品項解析成多項，造成 `ItemCount` / `ItemPrice` / `ItemAmt` 數量錯位 — 不是 ezPay 拒絕，是**靜默開出錯誤發票**。
- 四個 Request（`InvoiceIssueRequest` / `InvoiceTouchIssueRequest` / `InvoiceSearchRequest` / `AllowanceIssueRequest`）過去對 `merchantOrderNo` 各驗各的（長度只有 issue 有查、字元集全 0），現統一改用 `MerchantOrderNoValidator::assert()`。

### Added

- `InvoicePorter\Ezpay\Validation\MerchantOrderNoValidator`：4 個 Request 共用，集中規格避免漂移。`MAX_LENGTH = 20`、pattern `[A-Za-z0-9_]+`。
- `InvoicePorter\Ezpay\Validation\InvoiceItemFieldValidator`：`InvoiceItem` / `AllowanceItem` 共用，提供 `assertName()`（含 30 字長度與 `|` 檢查）、`assertUnit()`（`|` 檢查）。
- **`InvoiceIssueRequest` 補欄位驗證**（皆貼齊 EZP_INVI_1.2.2 第 5-1 章規格）：
  - `buyerName` ≤ 60 字（mb_strlen，下同含中文欄位）
  - `buyerAddress` ≤ 100 字
  - `buyerEmail` ≤ 80（filter_var + 長度）
  - `buyerUbn` 必須 8 碼純數字
  - `loveCode` 3–7 碼純數字
  - `carrierNum`：依 `carrierType` 套對應 regex
    - 手機條碼（Mobile）：`/^\/[A-Z0-9.\-+]{7}$/`
    - 自然人憑證（CitizenDigitalCertificate）：`/^[A-Z]{2}\d{14}$/`
    - 會員載具（Member）：藍新無固定格式，僅擋長度 ≤ 64
  - `comment` ≤ 200 字
  - 規格上限以 `public const` 暴露（`BUYER_NAME_MAX_LENGTH` 等），方便呼叫端做 UI 端對應檢查。
- `EzpayApiException` 錯誤碼語意分群 helper：
  - `errorCodePrefix(): string` — 取得錯誤碼前綴（如 `INV100`、`KEY100`）
  - `isFieldFormatError(): bool` — `INV100xx` / `INV700xx`（重試無意義）
  - `isAuthError(): bool` — `INV900xx` / `KEY100xx`（憑證 / 解密問題，告警 ops）
  - `isDuplicateOrderNo(): bool` — `NOR10001` / `LIB10003`（建議產生新編號重試）
- `tests/Unit/Validation/MerchantOrderNoValidatorTest`、`tests/Unit/Requests/Items/AllowanceItemTest`、`tests/Unit/Exceptions/EzpayApiExceptionTest` 共 22 支新測試。

### Changed

- `InvoiceInvalidRequest` / `AllowanceInvalidRequest::invalidReason` 加 docblock 註解為何使用 `mb_strlen` 而非 `strlen`（藍新後台中文「一字算一字」），避免下次貢獻者「修」回 strlen。
- `examples/*.php` 將 demo `merchantOrderNo` 範例從 `ORD-CHANGE-ME` 改為 `ORD_CHANGE_ME`、`'ORD-' . date(…)` 改為 `'ORD_' . date(…)`（連字號不符新規則）。

### Migration notes

- **BC break（驗證從寬變嚴）**：
  - 原本送 21–30 字元 `merchantOrderNo` 的呼叫端，現會在 `new XxxRequest(...)` 直接拋 `EzpayValidationException`（原本送出去後也會被藍新打回，只是 SDK 改為提早攔下）。
  - 原本 `merchantOrderNo` 含 `-`、空白、中文等非 `[A-Za-z0-9_]` 字元的呼叫端會被擋下 — **這是過去最常見的踩雷點**。請改用底線 `_` 作分隔符。
  - `InvoiceItem` / `AllowanceItem` 的 `name` / `unit` 含 `|` 字元的呼叫端會被擋下（過去會以資料毀損方式通過）。
  - `InvoiceItem` / `AllowanceItem` 的 `name` 超過 30 字會被擋下。
  - `InvoiceIssueRequest` 各 buyer / loveCode / carrierNum / comment 欄位若送格式不符值，會在 constructor 拋例外。
- 這些原本在藍新後端皆會失敗（除了 `|` 是 silent corruption），SDK 改為提早攔下屬意料內變動。

## [0.4.0] - 2026-05-11

第一位整合者（Laravel 使用者）回報的 4 點 DX feedback 一次到位：array-based config、cross-field 驗證提早攔下、公開查詢頁 sugar、時間欄位原生型別。順帶移除 0.3.0 仍標 `@deprecated` 的 `searchRedirectExit()`。

### Added

- `EzpayConfig::fromArray(array $cfg)`：array-based factory，方便 Laravel `config:cache` 後使用 `EzpayConfig::fromArray(config('ezpay'))`。只接受 snake_case keys 對齊 Laravel 慣例；unknown keys 忽略以保留使用者擴充空間。
- `EzpayInvoiceClient::publicQueryRedirectHtml(string $invoiceNumber, string $randomNum, string $merchantOrderNo, int|float $totalAmount): string`：剛 issue 完發票後快速跳轉到藍新公開查詢頁。內部包 `SearchType::ByInvoiceNumber` + `DisplayFlag::Redirect` 的 `InvoiceSearchRequest` 後委派給既有 `searchRedirectHtml()`，回傳自動 submit 的 form HTML（藍新查詢頁採 form-post，因此非 GET URL）。
- `InvoiceIssueResponse::toSearchRequest(): InvoiceSearchRequest`：從成功 issue 回應產生對應查詢 Request。配 `$client->searchRedirectHtml($response->toSearchRequest())` 使用最 DRY。回應缺 invoiceNumber / randomNum 時拋 `EzpayValidationException`。
- `createTimeAt(): ?DateTimeImmutable` 方法新增於 `InvoiceIssueResponse` / `InvoiceSearchResponse` / `InvoiceInvalidResponse` / `AllowanceIssueResponse` / `AllowanceTouchIssueResponse`，以及對應的 `invalidTimeAt(): ?DateTimeImmutable` 於 `AllowanceInvalidResponse`。解析藍新 `CreateTime`（格式 `Y-m-d H:i:s`）為原生 `DateTimeImmutable`，無 Carbon 依賴；使用 PHP `date_default_timezone_get()`；解析失敗或欄位缺則回 null。
- `EzpayResponse::parseDateTime(?string $value): ?DateTimeImmutable` protected helper — 給 Response 子類共用。

### Changed

- `EzpayConfig::fromEnv()` 內部重構為呼叫 `fromArray()`（DRY）。對外行為與錯誤訊息維持完全相同；既有測試與使用者程式碼零影響。
- `InvoiceIssueRequest`：B2C + Taxable + `PrintFlag::No` 且未提供 carrier 或 loveCode 時，現在會在 constructor 拋 `EzpayValidationException`（過去要送到藍新後端才會被擋）。其他 TaxType（ZeroRate / Exempt / Mixed）暫不做此檢查，等規範確認再加。
- `examples/01-issue.php`：補 `loveCode: '13994'` 以符合新驗證，且示範捐贈碼用法。

### Removed

- `EzpayInvoiceClient::searchRedirectExit()`（自 0.x 早期即 `@deprecated`，請改用 `searchRedirectHtml()` 或新的 `publicQueryRedirectHtml()`）。

### Migration notes

- 若程式碼曾用 B2C + 應稅 + `PrintFlag::No` 但未提供 `carrierType`/`carrierNum` 或 `loveCode`：0.4.0 起在 `new InvoiceIssueRequest(...)` 直接拋 `EzpayValidationException`。請補 carrier 或 loveCode。（原本也會在藍新後端失敗，只是 SDK 層提早攔下。）
- 若程式碼曾用 `$client->searchRedirectExit($request)`：改用 `echo $client->searchRedirectHtml($request); exit;` 或新的 `publicQueryRedirectHtml()`。

> 0.x 系列依語意化版本未保證向後相容；上述 soft-breaking 與 deprecated 移除屬意料內變動。

## [0.3.0] - 2026-05-11

首次發布版本。涵蓋藍新 EZPay 全部 7 個發票 / 折讓端點 + 3 個字軌管理端點，並預留多廠商擴充空間（綠界 ECPay、歐付寶 O'Pay、紅陽 Pay2Go 等）。

### 端點覆蓋（EZP_INVI_1.2.2，2024-04-22）

- `invoice_issue`（v1.5，含 `KioskPrintFlag`）
- `invoice_touch_issue`（v1.0）
- `invoice_invalid`（v1.0）
- `invoice_search`（v1.3，支援 `DisplayFlag=2` 回傳查詢結果 URL）
- `allowance_issue`（v1.3）
- `allowance_touch_issue`（v1.0）
- `allowanceInvalid`（v1.0）

### 字軌管理（EZP_Track_1.0.0，2018-10-03）

- `Api_number_management/createNumber`（v1.0）— 新增字軌
- `Api_number_management/manageNumber`（v1.0）— 字軌資料管理（變更狀態旗標）
- `Api_number_management/searchNumber`（v1.0）— 字軌資料查詢
- 新增 `EzpayTrackClient` facade（與 `EzpayInvoiceClient` 平級）
- 新增 `Enums\InvoiceTerm`（雙月期別）/ `Enums\TrackFlag`（字軌狀態）
- `EzpayConfig` 加 nullable `companyId` / `companyHashKey` / `companyHashIv` 三欄；既有發票欄位完全向後相容
- `EzpayConfig::fromEnv()` 同步讀 `EZPAY_COMPANY_ID` / `EZPAY_COMPANY_HASH_KEY` / `EZPAY_COMPANY_HASH_IV`
- 加密 / CheckCode 演算法與發票完全相同，重用 `Crypto\AesCryptor` / `Crypto\SignatureVerifier` / `Http\EzpayHttpClient` 零修改

### 主要功能

- Type-safe DTO：`EzpayConfig`、各 Request / Response、`InvoiceItem`、`AllowanceItem`
- 16 支 backed Enum 取代字串魔術值（`InvoiceStatus`、`Category`、`TaxType`、`PrintFlag`、`DisplayFlag`、…）
- 例外階層 `InvoiceException ← EzpayException ← {Validation, Api, CheckCode, Transport}`，呼叫端可一次 catch 共用根
- `Crypto\AesCryptor`（AES-256-CBC + PKCS7 32-byte padding + bin2hex）
- `Crypto\SignatureVerifier`（CheckCode SHA256 計算 / 驗證；對作廢與折讓系列採保守驗證策略）
- `Http\HttpClientFactory` 支援注入 `HandlerStack` 以便測試
- `Support\RedirectFormBuilder` — `DisplayFlag=1` 轉址 HTML 輸出可被框架包裝

### 多廠商擴充準備

- namespace 採 `InvoicePorter\<Vendor>\…` 結構（目前只有 `InvoicePorter\Ezpay\…`）
- `InvoicePorter\Exceptions\InvoiceException` 作為跨廠商共用 abstract base
- `docs/extending.md` 提供新廠商貢獻者擴充指南

### 工程

- 95 支發票 + ~32 支字軌單元 / 功能測試（含 Crypto、Enum、Request DTO 驗證、Client feature test、錯誤碼對映）
- PHPStan **level 10**、PHP-CS-Fixer（PSR-12 + `declare_strict_types`）
- GitHub Actions CI matrix（PHP 8.1 / 8.2 / 8.3）
- `examples/` 11 支獨立範例（`01-issue` … `08-search-redirect` 為發票，`09-track-create` … `11-track-search` 為字軌）+ `.env.example`

### 文件

- `README.md` — 設定、快速開始、發票 7 端點與字軌 3 端點使用範例、進階情境（B2B / 載具 / 捐贈 / 延遲開立 / 混合稅率）、CheckCode 策略、錯誤處理
- `docs/ezpay-api-mapping.md` — 藍新發票文件 vs SDK 對照表（升版用速查）
- `docs/ezpay-track-api-mapping.md` — 藍新字軌文件 vs SDK 對照表
- `docs/extending.md` — 新廠商擴充指南

### 系統需求

- PHP **8.1+**（內建 `ext-openssl`、`ext-json`）
- `guzzlehttp/guzzle` ^7.5
