# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

首次發布前的整備版本。涵蓋藍新 EZPay 全部 7 個發票 / 折讓端點 + 3 個字軌管理端點，並預留多廠商擴充空間（綠界 ECPay、歐付寶 O'Pay、紅陽 Pay2Go 等）。

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
- `demo/` 11 支獨立範例（`01-issue` … `08-search-redirect` 為發票，`09-track-create` … `11-track-search` 為字軌）+ `.env.example`

### 文件

- `README.md` — 設定、快速開始、發票 7 端點與字軌 3 端點使用範例、進階情境（B2B / 載具 / 捐贈 / 延遲開立 / 混合稅率）、CheckCode 策略、錯誤處理
- `docs/ezpay-api-mapping.md` — 藍新發票文件 vs SDK 對照表（升版用速查）
- `docs/ezpay-track-api-mapping.md` — 藍新字軌文件 vs SDK 對照表
- `docs/extending.md` — 新廠商擴充指南

### 系統需求

- PHP **8.1+**（內建 `ext-openssl`、`ext-json`）
- `guzzlehttp/guzzle` ^7.5
