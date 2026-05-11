# Security Policy

## 支援版本

invoice-porter 0.x 為 pre-1.0 階段，**僅最新 minor 版本**會收到安全修補。請隨升 patch / minor 版以取得修復。

## 回報漏洞

若發現以下任一情況，請**勿**在 public GitHub issue 揭露，請改以 email 私下聯絡：

- AES 加解密、CheckCode 計算、簽章驗證等密碼學相關瑕疵
- `EzpayConfig` / `EzpayInvoiceClient` 等公開 API 的注入或洩漏路徑
- PR / commit 中混入實際藍新（或其他廠商）測試 / 正式憑證
- 依賴套件（Guzzle 等）已知漏洞影響本 SDK

聯絡信箱：**dennykuo@gmail.com**

請於 email 內附上：

1. 影響版本與環境（PHP 版本、Guzzle 版本、是否 sandbox）
2. 重現步驟或最小 PoC
3. 建議的修補方向（如有）

收到後會在 7 個工作天內回覆首次評估，重大漏洞會優先發 patch 版並在 `CHANGELOG.md` 標註。

## 使用者責任

本 SDK 不負責保管您的藍新憑證。請務必：

- 把 `merchantId` / `hashKey` / `hashIv` 放在環境變數或 secret manager，**禁止** commit 進 repo
- `examples/.env` 已被 `.gitignore` 排除，不要把 `.env` 改名 commit
- 若懷疑憑證外洩（誤推到 GitHub、log 寫到非機密儲存），立即至藍新後台重新產製憑證

## 不在涵蓋範圍

下列項目不視為 SDK 漏洞：

- 您自行 hardcode 憑證後外洩
- 藍新後台側的問題（請聯絡藍新客服）
- 第三方 PHP 擴充（`ext-openssl`、`ext-json`）的問題
