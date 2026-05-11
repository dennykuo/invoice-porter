# Contributing

歡迎以 PR 形式為 invoice-porter 增加新廠商支援、修 bug、補測試或更新文件。

## 開發環境

```bash
composer install
composer ci   # cs-check + phpstan level 10 + phpunit
```

最低 PHP 8.1，但本機建議用 PHP 8.3 / 8.4 開發。

## 送 PR 前的檢查

1. `composer ci` 全綠（cs-check / PHPStan level 10 / PHPUnit）
2. 新功能附測試（Unit + Feature/Client 雙層）
3. 公開 API（Request DTO、Enum 值、Response method）異動須在 `CHANGELOG.md` 的 `[Unreleased]` 區塊註記
4. 文件保持同步（`README.md` / `docs/ezpay-api-mapping.md` 或對應新廠商 mapping）

## 新增廠商

請先讀 [`docs/extending.md`](docs/extending.md)，內含目錄 layout、可重用清單、禁止抽出共用層的原則、Exception 階層約定、測試慣例等。

新廠商 PR 至少需包含：

- `src/<Vendor>/` 完整實作（Crypto / Enums / Exceptions / Http / Requests / Responses / Support / Config / Client）
- `tests/Unit/<Vendor>/` + `tests/Feature/<Vendor>/Client/`
- `docs/<vendor>-api-mapping.md`
- README 加入新廠商「快速開始」段落

## 程式碼風格

- PSR-12 + `declare(strict_types=1)`
- single quote、short array
- import alpha 排序、no unused imports
- `composer cs-fix` 自動排版

## Commit 訊息

無嚴格格式要求，但建議：

- 使用祈使句（`add`、`fix`、`refactor` 而非 `added` / `fixing`）
- 第一行 ≤ 72 字元，必要時加空行 + 段落說明 why
- 同一個 PR 多個 commit 沒關係，merge 時若需要會 squash

## 測試憑證

**禁止**把任何實際藍新（或其他廠商）測試憑證寫死在 `tests/` 或 `examples/` 內。請用明顯的 dummy 值，例如：

```php
private const HASH_KEY = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';  // 32 個 a
private const HASH_IV  = 'bbbbbbbbbbbbbbbb';                  // 16 個 b
private const MERCHANT_ID = '00000000';
```

`examples/.env` 已被 gitignore，請放在 `.env` 內絕對不要 commit。

## 安全回報

若發現安全問題（如憑證洩漏、加解密邏輯瑕疵、PR 中混入私密金鑰），請見 [`SECURITY.md`](SECURITY.md)，**不要**直接開 public issue。
