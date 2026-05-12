# 藍新電子發票 API 對照（EZP_INVI_1.2.2 vs invoice-porter）

> 本文件僅描述**藍新（NewebPay/EZPay）「電子發票」7 個端點（EZP_INVI_1.2.2）**。藍新另有獨立的「電子發票字軌管理」3 個端點（EZP_Track_1.0.0），請見 [`docs/ezpay-track-api-mapping.md`](ezpay-track-api-mapping.md)。
>
> 未來其他廠商（綠界 ECPay、歐付寶 O'Pay、紅陽 Pay2Go 等）請見各自的 `docs/<vendor>-api-mapping.md`，擴充慣例見 [`docs/extending.md`](extending.md)。

對應藍新「電子發票串接技術文件」**EZP_INVI_1.2.2**（2024-04-22）。本文件供未來文件版本升級時對照使用，可快速找出哪些 enum / DTO 需要追加或調整。

> 文件下載：藍新後台 → 商務後台 → 開發整合 → 電子發票 API 文件

## 端點總覽

| 文件章節 | 中文名稱 | 端點 URI | 文件版本 | SDK 方法 | SDK 類別 |
|----------|---------|---------|---------|---------|---------|
| 5-1 | 開立發票 | `invoice_issue` | 1.5 | `EzpayInvoiceClient::issue()` | `Requests\InvoiceIssueRequest` |
| 5-2 | 觸發開立發票 | `invoice_touch_issue` | 1.0 | `EzpayInvoiceClient::touchIssue()` | `Requests\InvoiceTouchIssueRequest` |
| 5-3 | 作廢發票 | `invoice_invalid` | 1.0 | `EzpayInvoiceClient::invalid()` | `Requests\InvoiceInvalidRequest` |
| 5-4 | 開立折讓 | `allowance_issue` | 1.3 | `EzpayInvoiceClient::issueAllowance()` | `Requests\AllowanceIssueRequest` |
| 5-5 | 觸發/取消折讓 | `allowance_touch_issue` | 1.0 | `EzpayInvoiceClient::touchAllowance()` | `Requests\AllowanceTouchIssueRequest` |
| 5-6 | 作廢折讓 | `allowanceInvalid`（駝峰） | 1.0 | `EzpayInvoiceClient::invalidAllowance()` | `Requests\AllowanceInvalidRequest` |
| 5-7 | 查詢發票 | `invoice_search` | 1.3 | `EzpayInvoiceClient::search()` / `searchRedirectHtml()` | `Requests\InvoiceSearchRequest` |

> 注意：作廢折讓的 URI 為 **駝峰命名 `allowanceInvalid`**，與其他端點的底線命名不同。請見 `AllowanceInvalidRequest::uri()` 註解。

## 共用機制

| 文件描述 | 實作位置 |
|---------|---------|
| AES-256-CBC 加密 + PKCS7 32-byte block padding + bin2hex（附錄一） | `Ezpay\Crypto\AesCryptor` |
| CheckCode 計算（5 欄 ksort + http_build_query + SHA256 大寫，附錄二） | `Ezpay\Crypto\SignatureVerifier` |
| Sandbox / Production base URL | `Ezpay\Environment::baseUrl()` |
| 統一參數 `MerchantID_` + `PostData_` 包裝 | `Ezpay\EzpayInvoiceClient::send()` |

## Enum 對照

| 藍新欄位 | 文件值 | SDK Enum |
|---------|-------|---------|
| `RespondType` | `JSON` / `String` | `Enums\RespondType` |
| `Status`（開立） | `0` 待開立、`1` 立即開立、`3` 延遲開立 | `Enums\InvoiceStatus` |
| `Status`（折讓開立） | `0` 待確認、`1` 立即開立 | `Enums\AllowanceConfirmStatus` |
| `Status`（折讓觸發） | `C` 確認、`D` 取消 | `Enums\AllowanceTouchStatus` |
| `Category` | `B2B` / `B2C` | `Enums\Category` |
| `TaxType` | `1` 應稅、`2` 零稅率、`3` 免稅、`9` 混合 | `Enums\TaxType` |
| `CarrierType` | `0` 會員、`1` 手機、`2` 自然人憑證 | `Enums\CarrierType` |
| `PrintFlag` | `Y` / `N` | `Enums\PrintFlag` |
| `KioskPrintFlag`（v1.5 新增） | `1` 啟用 KIOSK 列印 | `Enums\KioskPrintFlag` |
| `CustomsClearance` | `1` 非經海關、`2` 經海關 | `Enums\CustomsClearance` |
| `SearchType` | `0` 用發票號碼、`1` 用商家訂單編號 | `Enums\SearchType` |
| `DisplayFlag` | `1` Redirect、`2` ResultUrl（v1.2.2 新增） | `Enums\DisplayFlag` |
| `InvoiceStatus`（查詢回應，發票生命週期） | `1` 已開立、`2` 已作廢 | `Enums\InvoiceLifecycleStatus` |
| `UploadStatus` | `0` 未上傳、`1` 已上傳、`2` 待上傳、`3` 上傳失敗、`4` 消費者已上傳 | `Enums\UploadStatus` |
| `InvoiceType` | `07` 一般、`08` 特種 | `Enums\InvoiceType` |

## CheckCode 策略

文件附錄二記載 5 欄參與 SHA256：`InvoiceTransNo`、`MerchantID`、`MerchantOrderNo`、`RandomNum`、`TotalAmt`。

| 端點 | 預設行為 | 可控制 |
|------|---------|-------|
| `invoice_issue` | **驗證** | — |
| `invoice_touch_issue` | **驗證** | — |
| `invoice_search`（API 模式） | **驗證** | — |
| `invoice_search`（DisplayFlag=1 Redirect） | 跳過 | — |
| `invoice_invalid` | 跳過 | 帶齊 `randomNum`/`invoiceTransNo`/`merchantOrderNo`/`totalAmount` 即啟用 |
| `allowance_issue` | 跳過 | `expectCheckCode: true` 開啟 |
| `allowance_touch_issue` | 跳過 | `expectCheckCode: true` 開啟 |
| `allowanceInvalid` | 跳過 | `expectCheckCode: true` 開啟 |

> 折讓與作廢系列藍新回應實際是否含 5 欄並未在文件明確規範。SDK 採保守策略，使用者可依當下後台行為決定是否強制驗證。

## 例外類型

```
InvoiceException (abstract, RuntimeException)        # 跨廠商共用根
└── EzpayException (abstract)
    ├── EzpayValidationException     # DTO 階段驗證失敗
    ├── EzpayApiException            # 業務錯誤碼（含 errorCode、message、rawResponse）
    ├── EzpayCheckCodeException      # CheckCode 不符或欄位不足
    └── EzpayTransportException      # 網路、5xx、JSON 解析失敗
```

常見業務錯誤碼（SDK 不做 enum 化，由呼叫端 string-match）：

| 錯誤碼 | 文件描述 |
|--------|---------|
| `KEY10002` | 解密失敗 |
| `INV10003` | 必要參數不齊 |
| `LIB10005` | 商店不存在 |
| `NOR10001` | 訂單編號重複 |
| `IAI10001` | 發票號碼錯誤 |

完整清單請見藍新文件附錄三。

## 欄位規格 vs SDK 驗證 cheatsheet

> 以下整理「**藍新規格上有但 SDK 不一定有擋下**」的欄位，免去逐條翻官方 PDF。✅ = SDK 會在 `new XxxRequest(...)` 直接擋下；❌ = SDK 不檢查，會由藍新後端在送出後打回；➖ = 不適用。
>
> SDK 採「**有規格就擋，沒規格不擋**」策略 — 規格不明的欄位（如 Member 載具）只擋空 / 過長等基本保險。

### MerchantOrderNo

| 規則 | 規格 | SDK | 出處 |
|------|------|-----|------|
| 必填 | 是 | ✅ | `MerchantOrderNoValidator::assert()` |
| 長度 | Varchar(20) | ✅ | `MerchantOrderNoValidator::MAX_LENGTH` |
| 字元集 | `[A-Za-z0-9_]+` | ✅ | `MerchantOrderNoValidator::PATTERN` |
| 統一驗證點 | — | ✅ | 4 個 Request 統一呼叫此 validator（0.4.1 起） |

### InvoiceIssueRequest 買方欄位

| 欄位 | 規格 | SDK | 備註 |
|------|------|-----|------|
| `BuyerName` | Varchar(60) | ✅ 長度（mb_strlen） | `InvoiceIssueRequest::BUYER_NAME_MAX_LENGTH` |
| `BuyerAddress` | Varchar(100) | ✅ 長度（mb_strlen） | `InvoiceIssueRequest::BUYER_ADDRESS_MAX_LENGTH` |
| `BuyerEmail` | Varchar(80) | ✅ 格式 + 長度 | `filter_var` + `BUYER_EMAIL_MAX_LENGTH` |
| `BuyerUBN` | 8 碼純數字 | ✅ regex `/^\d{8}$/` | B2B 必填亦由 SDK 擋下 |

### 載具 / 愛心碼

| 欄位 | 規格 | SDK | 備註 |
|------|------|-----|------|
| `LoveCode` | 3–7 碼純數字 | ✅ regex `/^\d{3,7}$/` | |
| `CarrierNum`（手機條碼） | `/^\/[A-Z0-9.\-+]{7}$/` | ✅ | `CarrierType::Mobile` |
| `CarrierNum`（自然人憑證） | `/^[A-Z]{2}\d{14}$/` | ✅ | `CarrierType::CitizenDigitalCertificate` |
| `CarrierNum`（會員載具） | 廠商自訂 | ⚠️ 僅擋長度 ≤ 64 | `CARRIER_NUM_MEMBER_MAX_LENGTH` |

### 品項與其他

| 欄位 | 規格 | SDK | 備註 |
|------|------|-----|------|
| `InvoiceItem.name` / `AllowanceItem.name` | Varchar(30)，不可含 `\|` | ✅ 長度 + `\|` 檢查 | `InvoiceItemFieldValidator::assertName()` |
| `InvoiceItem.unit` / `AllowanceItem.unit` | 不可含 `\|` | ✅ | 多 item 以 `\|` 串接送出，含此字元會 silent corruption |
| `Comment` | Varchar(200) | ✅ 長度（mb_strlen） | `InvoiceIssueRequest::COMMENT_MAX_LENGTH` |
| `InvalidReason`（作廢理由） | Varchar(20) | ✅ 長度（mb_strlen） | 中文一字算一字 |

### 跨欄位 invariants

> 規格上「同時送會被退」「擇一」「互斥」的硬約束。✅ = SDK 在 constructor 直接擋下；❌ = 由藍新後端打回。

| 規則 | SDK | 自版本 |
|------|-----|-------|
| B2B → `buyerUbn` 必填 | ✅ | 0.3.0 |
| Status=3 → `createStatusTime` 必填 | ✅ | 0.3.0 |
| **B2C + `PrintFlag::No` → 必須提供 carrier 或 loveCode（全 TaxType）** | ✅ | 0.5.0（過去 0.4.0 只擋 Taxable） |
| **載具（carrier）與捐贈碼（loveCode）互斥** | ✅ | 0.5.0 |
| **B2B 不可使用載具** | ✅ | 0.5.0 |
| **B2B 不可使用捐贈碼** | ✅ | 0.5.0 |
| **`carrierType` / `carrierNum` 必須同時提供或同時省略** | ✅ explicit error | 0.5.0（過去 silent treat as missing） |
| TaxType=9（Mixed）→ 每個 item 必須給 `taxType` | ❌（藍新後端會打回；待補） | — |
| `Amt` + `TaxAmt` = `TotalAmt` | ❌（呼叫端自行計算） | — |

### 必填欄位（無預設）

| 欄位 | 必填自 | 備註 |
|------|-------|------|
| `status` / `merchantOrderNo` / `category` / `taxType` / `amount` / `taxAmount` / `totalAmount` / `items` | 0.3.0 | constructor 第 1–8 個參數 |
| **`printFlag`** | 0.5.0 | 0.4.x 預設 `PrintFlag::No`，配 B2C 強制 carrier/loveCode 成為「預設陷阱」，故移除預設改必填 |

> 「❌」欄位不代表永遠不該擋，而是當前沒擋 — 視踩雷頻率決定是否補。歡迎以 PR 補強。

## 對應的藍新文件版本

目前實作對應 **EZP_INVI_1.2.2（2024-04-22）**：全 7 端點覆蓋；`invoice_issue` v1.5（含 `KioskPrintFlag`）、`invoice_search` v1.3（含 `DisplayFlag=2`）。

## 文件升版檢查清單（給未來的自己）

當藍新發布新版文件時，依序確認：

1. **端點 URI / Version 是否變更** → 對應 `Requests\*Request::uri()` / `version()`
2. **新增欄位** → 對應 Request DTO 的 constructor + `toEncryptablePayload()`
3. **新增 enum 值** → 對應 `Enums/`
4. **新增端點** → 新增 Request / Response / facade 方法
5. **CheckCode 演算法是否變更** → 動 `Crypto\SignatureVerifier`
6. **新錯誤碼** → 補進本文件「常見業務錯誤碼」與 `tests/Feature/Client/ErrorMappingTest`
