<?php

declare(strict_types=1);

/**
 * Invoice Porter — demo 索引頁面。
 *
 * 啟動方式：
 *   php -S localhost:8000 -t demo
 *   瀏覽 http://localhost:8000
 *
 * 注意：實際執行 demo 仍以 CLI 為主（如 `php demo/01-issue.php`）。
 * 本頁僅作索引與原始碼預覽，不在瀏覽器執行 demo（缺 .env 時會炸、輸出也是純文字）。
 */

$demos = [
    'invoice' => [
        'title' => '發票 API',
        'subtitle' => 'EZP_INVI_1.2.2 · 7 endpoints',
        'description' => '開立、觸發開立、作廢、查詢，以及折讓三件套。',
        'items' => [
            ['no' => '01', 'file' => '01-issue.php',            'name' => '開立發票',         'method' => 'issue()',                'endpoint' => 'invoice_issue',         'version' => '1.5'],
            ['no' => '02', 'file' => '02-touch-issue.php',      'name' => '觸發開立',         'method' => 'touchIssue()',           'endpoint' => 'invoice_touch_issue',   'version' => '1.0'],
            ['no' => '03', 'file' => '03-invalid.php',          'name' => '作廢發票',         'method' => 'invalid()',              'endpoint' => 'invoice_invalid',       'version' => '1.0'],
            ['no' => '04', 'file' => '04-search.php',           'name' => '查詢發票（API）',   'method' => 'search()',               'endpoint' => 'invoice_search',        'version' => '1.3'],
            ['no' => '05', 'file' => '05-allowance-issue.php',  'name' => '開立折讓',         'method' => 'issueAllowance()',       'endpoint' => 'allowance_issue',       'version' => '1.3'],
            ['no' => '06', 'file' => '06-allowance-touch.php',  'name' => '觸發/取消折讓',    'method' => 'touchAllowance()',       'endpoint' => 'allowance_touch_issue', 'version' => '1.0'],
            ['no' => '07', 'file' => '07-allowance-invalid.php', 'name' => '作廢折讓',         'method' => 'invalidAllowance()',     'endpoint' => 'allowanceInvalid',      'version' => '1.0'],
            ['no' => '08', 'file' => '08-search-redirect.php',  'name' => '查詢發票（轉址）', 'method' => 'searchRedirectHtml()',   'endpoint' => 'invoice_search',        'version' => '1.3'],
        ],
    ],
    'track' => [
        'title' => '字軌 API',
        'subtitle' => 'EZP_Track_1.0.0 · 3 endpoints',
        'description' => '會員（公司）層級 API；需設 nullable 公司憑證。',
        'items' => [
            ['no' => '09', 'file' => '09-track-create.php',  'name' => '新增字軌',     'method' => 'trackCreate()', 'endpoint' => 'Api_number_management/createNumber', 'version' => '1.0'],
            ['no' => '10', 'file' => '10-track-manage.php',  'name' => '字軌資料管理', 'method' => 'trackManage()', 'endpoint' => 'Api_number_management/manageNumber', 'version' => '1.0'],
            ['no' => '11', 'file' => '11-track-search.php',  'name' => '字軌資料查詢', 'method' => 'trackSearch()', 'endpoint' => 'Api_number_management/searchNumber', 'version' => '1.0'],
        ],
    ],
];

// 建立白名單，避免 ?view= 任意路徑遍歷
$allowedFiles = [];
foreach ($demos as $section) {
    foreach ($section['items'] as $item) {
        $allowedFiles[] = $item['file'];
    }
}

$viewFile = isset($_GET['view']) && is_string($_GET['view']) ? basename($_GET['view']) : null;
if ($viewFile !== null && !in_array($viewFile, $allowedFiles, true)) {
    http_response_code(404);
    exit('Demo file not found.');
}

// 把 highlight_file 的預設色改成 dark theme 友善
ini_set('highlight.string', '#9ece6a');
ini_set('highlight.comment', '#5a6373');
ini_set('highlight.keyword', '#5eead4');
ini_set('highlight.default', '#c0caf5');
ini_set('highlight.html', '#c0caf5');

function findDemo(array $demos, string $file): ?array
{
    foreach ($demos as $section) {
        foreach ($section['items'] as $item) {
            if ($item['file'] === $file) {
                return $item;
            }
        }
    }
    return null;
}

?><!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice Porter · Demo<?php if ($viewFile !== null) {
    echo ' · ' . htmlspecialchars($viewFile, ENT_QUOTES);
} ?></title>
<style>
:root {
    --bg: #0b0d10;
    --surface: #14171c;
    --surface-2: #1c2026;
    --border: #262b33;
    --text: #e7eaee;
    --text-dim: #8a93a0;
    --accent: #5eead4;
    --accent-soft: rgba(94, 234, 212, 0.18);
    --code-amber: #ffd87a;
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", "PingFang TC", "Microsoft JhengHei", sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}
code, pre, .mono {
    font-family: "JetBrains Mono", "SF Mono", "Menlo", Consolas, "Roboto Mono", monospace;
}
.wrap {
    max-width: 920px;
    margin: 0 auto;
    padding: 56px 24px 96px;
}

/* ─── header ─── */
header.top h1 {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 6px 0;
    letter-spacing: -0.01em;
}
header.top h1 .dot {
    color: var(--text-dim);
    font-weight: 300;
    margin: 0 8px;
}
header.top .sub {
    color: var(--text-dim);
    font-size: 15px;
    margin: 0 0 28px 0;
}

/* ─── hint ─── */
.hint {
    background: var(--surface);
    border: 1px solid var(--border);
    border-left: 3px solid var(--accent);
    border-radius: 8px;
    padding: 16px 20px;
    margin: 0 0 40px;
    font-size: 13.5px;
    color: var(--text-dim);
}
.hint strong { color: var(--accent); font-weight: 500; }
.hint code {
    background: var(--surface-2);
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 12.5px;
    color: var(--code-amber);
    border: 1px solid var(--border);
}

/* ─── group ─── */
section.group { margin-top: 36px; }
section.group .group-head {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 6px;
}
section.group h2 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    color: var(--text);
}
section.group .group-version {
    font-size: 12px;
    color: var(--text-dim);
}
section.group .group-desc {
    color: var(--text-dim);
    font-size: 13px;
    margin: 0 0 16px;
}

/* ─── demo list ─── */
.demo-list {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    background: var(--surface);
}
.demo-row {
    display: grid;
    grid-template-columns: 48px 1fr 18px;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
    color: inherit;
    transition: background 120ms ease;
}
.demo-row:last-child { border-bottom: none; }
.demo-row:hover { background: var(--surface-2); }
.demo-row .num {
    font-size: 13px;
    color: var(--text-dim);
    font-weight: 500;
    text-align: left;
}
.demo-row .meta .name {
    font-weight: 500;
    font-size: 14.5px;
    color: var(--text);
    margin-bottom: 2px;
}
.demo-row .meta .detail {
    font-size: 12px;
    color: var(--text-dim);
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.demo-row .meta .detail .method { color: var(--accent); }
.demo-row .meta .detail .sep { opacity: 0.4; }
.demo-row .arrow {
    color: var(--text-dim);
    font-size: 14px;
    transition: transform 160ms ease, color 160ms ease;
    text-align: right;
}
.demo-row:hover .arrow {
    color: var(--accent);
    transform: translateX(3px);
}

/* ─── footer ─── */
footer {
    margin-top: 64px;
    padding-top: 22px;
    border-top: 1px solid var(--border);
    color: var(--text-dim);
    font-size: 12.5px;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: space-between;
}
footer a {
    color: var(--text-dim);
    text-decoration: none;
    border-bottom: 1px dashed transparent;
    transition: color 120ms ease, border-bottom-color 120ms ease;
}
footer a:hover {
    color: var(--accent);
    border-bottom-color: var(--accent-soft);
}

/* ─── source view ─── */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--text-dim);
    text-decoration: none;
    font-size: 13px;
    margin-bottom: 22px;
    transition: color 120ms ease;
}
.back-link:hover { color: var(--accent); }
.source-head {
    margin: 0 0 6px;
    font-size: 22px;
    font-weight: 600;
}
.source-meta {
    color: var(--text-dim);
    font-size: 12.5px;
    margin: 0 0 22px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.source-meta .pill {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 12px;
}
.source-meta .pill.method { color: var(--accent); border-color: var(--accent-soft); }
.source-cmd {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px 10px 18px;
    margin-bottom: 22px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 14px;
}
.source-cmd .cmd-text {
    flex: 1;
    min-width: 0;
    overflow-x: auto;
    white-space: nowrap;
    font-size: 13px;
}
.source-cmd .prompt { color: var(--text-dim); user-select: none; }
.source-cmd .cmd { color: var(--accent); }
.copy-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--surface-2);
    color: var(--text-dim);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-family: "JetBrains Mono", "SF Mono", Menlo, Consolas, monospace;
    font-size: 12px;
    line-height: 1;
    cursor: pointer;
    transition: background 140ms ease, color 140ms ease, border-color 140ms ease;
    user-select: none;
}
.copy-btn .copy-icon { font-size: 13px; line-height: 1; }
.copy-btn:hover {
    color: var(--accent);
    border-color: var(--accent-soft);
    background: var(--surface);
}
.copy-btn.copied {
    color: var(--accent);
    border-color: var(--accent);
    background: var(--accent-soft);
}
.copy-btn.failed {
    color: #f87171;
    border-color: #f8717155;
}
@media (prefers-reduced-motion: reduce) {
    .copy-btn { transition: none; }
}
.source-code {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 22px 24px;
    overflow: auto;
    font-size: 12.5px;
    line-height: 1.75;
}
.source-code code { background: none; padding: 0; }
.source-code pre, .source-code code > span {
    background: none !important;
    margin: 0;
}
</style>
</head>
<body>
<div class="wrap">

<?php if ($viewFile !== null) { ?>
    <?php
    $info = findDemo($demos, $viewFile);
    $sourcePath = __DIR__ . '/' . $viewFile;
    ?>
    <a href="?" class="back-link">← 返回列表</a>
    <h1 class="source-head"><?= htmlspecialchars($info['name'] ?? $viewFile, ENT_QUOTES) ?></h1>
    <div class="source-meta">
        <span class="pill mono"><?= htmlspecialchars($viewFile, ENT_QUOTES) ?></span>
        <span class="pill mono method"><?= htmlspecialchars($info['method'] ?? '', ENT_QUOTES) ?></span>
        <span class="pill mono"><?= htmlspecialchars($info['endpoint'] ?? '', ENT_QUOTES) ?></span>
        <?php if (!empty($info['version'])) { ?>
            <span class="pill mono">v<?= htmlspecialchars($info['version'], ENT_QUOTES) ?></span>
        <?php } ?>
    </div>
    <?php $cliCmd = 'php demo/' . $viewFile; ?>
    <div class="source-cmd">
        <div class="cmd-text mono">
            <span class="prompt">$ </span><span class="cmd">php demo/<?= htmlspecialchars($viewFile, ENT_QUOTES) ?></span>
        </div>
        <button type="button" class="copy-btn" data-copy="<?= htmlspecialchars($cliCmd, ENT_QUOTES) ?>" aria-label="複製命令到剪貼簿">
            <span class="copy-icon" aria-hidden="true">⧉</span>
            <span class="copy-label">複製</span>
        </button>
    </div>
    <div class="source-code"><?php
        if (file_exists($sourcePath)) {
            highlight_file($sourcePath);
        } else {
            echo '<pre>// File not found.</pre>';
        }
    ?></div>
<?php } else { ?>

<header class="top">
    <h1>Invoice Porter <span class="dot">·</span> <span style="color:var(--text-dim);font-weight:400">Demo</span></h1>
    <p class="sub">藍新（NewebPay/EZPay）電子發票 PHP SDK · 範例索引</p>
</header>

<div class="hint">
    <strong>使用前</strong>　複製 <code>demo/.env.example</code> 為 <code>demo/.env</code> 並填入測試憑證，
    再以 CLI 執行：<code>php demo/01-issue.php</code>。本頁僅作索引與原始碼預覽。
</div>

<?php foreach ($demos as $key => $group) { ?>
<section class="group">
    <div class="group-head">
        <h2><?= htmlspecialchars($group['title'], ENT_QUOTES) ?></h2>
        <span class="group-version mono"><?= htmlspecialchars($group['subtitle'], ENT_QUOTES) ?></span>
    </div>
    <p class="group-desc"><?= htmlspecialchars($group['description'], ENT_QUOTES) ?></p>
    <div class="demo-list">
        <?php foreach ($group['items'] as $item) { ?>
        <a class="demo-row" href="?view=<?= urlencode($item['file']) ?>">
            <span class="num mono"><?= htmlspecialchars($item['no'], ENT_QUOTES) ?></span>
            <span class="meta">
                <div class="name"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></div>
                <div class="detail mono">
                    <span class="method"><?= htmlspecialchars($item['method'], ENT_QUOTES) ?></span>
                    <span class="sep">·</span>
                    <span><?= htmlspecialchars($item['endpoint'], ENT_QUOTES) ?></span>
                    <span class="sep">·</span>
                    <span>v<?= htmlspecialchars($item['version'], ENT_QUOTES) ?></span>
                </div>
            </span>
            <span class="arrow">→</span>
        </a>
        <?php } ?>
    </div>
</section>
<?php } ?>

<footer>
    <span>11 demos · 2 modules · <span class="mono">php -S localhost:8000 -t demo</span></span>
    <span>
        <a href="https://github.com/dennykuo/invoice-porter" target="_blank" rel="noopener">GitHub</a>
        &nbsp;·&nbsp;
        <a href="https://github.com/dennykuo/invoice-porter/blob/master/docs/ezpay-api-mapping.md" target="_blank" rel="noopener">發票對照</a>
        &nbsp;·&nbsp;
        <a href="https://github.com/dennykuo/invoice-porter/blob/master/docs/ezpay-track-api-mapping.md" target="_blank" rel="noopener">字軌對照</a>
    </span>
</footer>

<?php } ?>

</div>
<script>
(function () {
    var buttons = document.querySelectorAll('.copy-btn');
    if (buttons.length === 0) return;

    function flash(btn, label, icon, klass, ms) {
        var labelEl = btn.querySelector('.copy-label');
        var iconEl = btn.querySelector('.copy-icon');
        if (!labelEl || !iconEl) return;
        var origLabel = labelEl.textContent;
        var origIcon = iconEl.textContent;
        labelEl.textContent = label;
        iconEl.textContent = icon;
        btn.classList.add(klass);
        setTimeout(function () {
            labelEl.textContent = origLabel;
            iconEl.textContent = origIcon;
            btn.classList.remove(klass);
        }, ms);
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'absolute';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(ta);
        return ok;
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cmd = btn.dataset.copy || '';
            var done = function (success) {
                if (success) {
                    flash(btn, '已複製', '✓', 'copied', 1600);
                } else {
                    flash(btn, '複製失敗', '⚠', 'failed', 1800);
                }
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(cmd).then(function () { done(true); }, function () {
                    done(fallbackCopy(cmd));
                });
            } else {
                done(fallbackCopy(cmd));
            }
        });
    });
})();
</script>
</body>
</html>
