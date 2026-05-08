# 藍新電子發票字軌 API 對照（EZP_Track_1.0.0 vs invoice-porter）

> 本文件僅描述**藍新（NewebPay/EZPay）「電子發票字軌管理」**。發票端點請見 [`docs/ezpay-api-mapping.md`](ezpay-api-mapping.md)。

對應藍新「電子發票字軌管理」**EZP_Track_1.0.0**（2018-10-03 初版唯一版本，38 頁）。本文件供未來文件版本升級時對照使用，可快速找出哪些 enum / DTO 需要追加或調整。

> 文件下載：藍新後台 → 商務後台 → 開發整合 → 電子發票字軌管理 API 文件

## 概念差異：商店 vs 公司

字軌 API 與發票 API 的最大差異是**認證層級不同**：

|  | 發票 API | 字軌 API |
|---|---------|---------|
| 文件 | EZP_INVI_1.2.2 | **EZP_Track_1.0.0** |
| 認證層級 | 商店（每家店都有獨立 MerchantID） | **會員 / 公司**（一個公司一組 CompanyID） |
| envelope 第一欄 | `MerchantID_` | **`CompanyID_`** |
| 加密金鑰 | 商店 HashKey / HashIv | **公司** HashKey / HashIv（不同金鑰） |
| CheckCode 第一個欄位 | `MerchantID` | **`CompanyId`**（駝峰） |
| URL prefix | `invoice_*` / `allowance*` | **`Api_number_management/*`** |

> 因此本 SDK 把字軌獨立成 `EzpayTrackClient`，與 `EzpayInvoiceClient` 平級。`EzpayConfig` 同時保有商店與公司憑證欄位（公司欄位 nullable，僅使用字軌時才必填）。

## 端點總覽

| 文件章節 | 中文名稱 | 端點 URI | 文件版本 | SDK 方法 | SDK 類別 |
|----------|---------|---------|---------|---------|---------|
| 5-1 | 新增字軌 | `Api_number_management/createNumber` | 1.0 | `EzpayTrackClient::trackCreate()` | `Requests\Track\TrackCreateRequest` |
| 5-2 | 字軌資料管理 | `Api_number_management/manageNumber` | 1.0 | `EzpayTrackClient::trackManage()` | `Requests\Track\TrackManageRequest` |
| 5-3 | 字軌資料查詢 | `Api_number_management/searchNumber` | 1.0 | `EzpayTrackClient::trackSearch()` | `Requests\Track\TrackSearchRequest` |

## 共用機制（與發票完全相同，零修改重用）

| 文件描述 | 實作位置 |
|---------|---------|
| AES-256-CBC 加密 + PKCS7 32-byte block padding + bin2hex（附錄一） | `Ezpay\Crypto\AesCryptor` |
| CheckCode 計算（5 欄 ksort + http_build_query + SHA256 大寫，附錄二） | `Ezpay\Crypto\SignatureVerifier` |
| Sandbox / Production base URL | `Ezpay\Environment::baseUrl()` |
| 統一參數 envelope 包裝 | `Ezpay\EzpayTrackClient::send()`（envelope 用 `CompanyID_`） |

> 加密 / 簽章演算法與發票 100% 相同；本 SDK 不為字軌單獨開新的 Crypto 類別，而是用相同的 `AesCryptor` / `SignatureVerifier`，只是建構時餵入「公司」金鑰而非「商店」金鑰。

## Enum 對照

| 藍新欄位 | 文件值 | SDK Enum | 重用既有 |
|---------|-------|---------|---------|
| `RespondType` | `JSON` / `String` | `Enums\RespondType` | ✓ |
| `Term` | `1` 一二月、`2` 三四月、`3` 五六月、`4` 七八月、`5` 九十月、`6` 十一十二月 | **`Enums\InvoiceTerm`** | 新增 |
| `Type` | `07` 一般稅額、`08` 特種稅額 | `Enums\InvoiceType` | ✓ |
| `Flag` | `0` 暫停、`1` 正常、`2` 停止 | **`Enums\TrackFlag`** | 新增 |

## CheckCode 策略

文件附錄二記載字軌 5 欄參與 SHA256：`AphabeticLetter`、`CompanyId`、`EndNumber`、`ManagementNo`、`StartNumber`。

> 注意：CheckCode 內變數名為 `CompanyId`（駝峰），不是 `CompanyID`（全大寫）。這是文件附錄二實際標示的命名。

| 端點 | 預設行為 | 可控制 |
|------|---------|-------|
| `createNumber` | **驗證** | — |
| `manageNumber` | 跳過 | `expectCheckCode: true` 開啟 |
| `searchNumber` | 跳過 | `expectCheckCode: true` 開啟 |

> 折讓系列保守策略：藍新部分回應實際是否含 5 欄並未在文件明確規範，使用者可依當下後台行為決定是否強制驗證。

## 例外類型

字軌 API 與發票共用同一階層：

```
InvoiceException (abstract, RuntimeException)        # 跨廠商共用根
└── EzpayException (abstract)
    ├── EzpayValidationException     # DTO 階段驗證失敗
    ├── EzpayApiException            # 業務錯誤碼（含 errorCode、message、rawResponse）
    ├── EzpayCheckCodeException      # CheckCode 不符或欄位不足
    └── EzpayTransportException      # 網路、5xx、JSON 解析失敗
```

字軌相關業務錯誤碼（SDK 不做 enum 化，由呼叫端 string-match）：

| 錯誤碼 | 文件描述 |
|--------|---------|
| `INM10001` | 字軌已存在 |
| `INM10002` | 字軌號碼錯誤 |
| `INM10003` | 字軌期別錯誤 |
| `INM10004` | 字軌起訖號碼錯誤 |
| `INM10005` | 字軌已被使用 |
| `INM10006` | 查無字軌資料 |
| `MOD10001` / `MOD10003` / `MOD10006` | 系統 / 模組錯誤 |
| `LIB10001` / `LIB10004` / `LIB10007` / `LIB10010` / `LIB10011` / `LIB10013` / `LIB10016` | 函式庫層級錯誤（必填、格式、商家 / 公司不存在、簽章錯誤） |
| `SET10006` | 設定錯誤 |
| `KEY10006` / `KEY10010` / `KEY10011` | 金鑰錯誤 / 過期 / 未啟用 |

完整清單請見藍新文件附錄三。本 SDK 在 `tests/Feature/Client/Track/TrackErrorMappingTest` 對 18 個常見錯誤碼做 dataProvider 覆蓋。

## 對應的藍新文件版本

目前實作對應 **EZP_Track_1.0.0（2018-10-03，初版唯一版本）**：3 端點完整覆蓋。

## 文件升版檢查清單（給未來的自己）

當藍新發布新版字軌文件時，依序確認：

1. **端點 URI / Version 是否變更** → 對應 `Requests\Track\*Request::uri()` / `version()`
2. **新增欄位** → 對應 Request DTO 的 constructor + `toEncryptablePayload()`
3. **新增 enum 值** → 對應 `Enums/InvoiceTerm` / `Enums/TrackFlag`
4. **新增端點** → 新增 Request / Response / facade 方法
5. **CheckCode 演算法是否變更** → 動 `Crypto\SignatureVerifier`
6. **新錯誤碼** → 補進本文件「業務錯誤碼」與 `tests/Feature/Client/Track/TrackErrorMappingTest`
