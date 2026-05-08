<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Responses\Track;

use InvoicePorter\Ezpay\Responses\EzpayResponse;

/**
 * 字軌資料查詢的回應（EZP_Track_1.0.0 §5-3）。
 *
 * 與單筆建立 / 管理不同，搜尋會回傳一個字軌陣列。本 Response 提供 `items()` 直接拿原始 array，
 * 同時為單筆查詢情境提供便捷存取 `firstManagementNo()`。
 */
final class TrackSearchResponse extends EzpayResponse
{
    /**
     * 取得 Result 內所有字軌項目的原始陣列。
     *
     * 藍新搜尋的 Result 可能是「單一物件」或「物件陣列」（由 SearchData / 多筆 Result 包裝）。
     * 為避免猜測欄位名，這裡優先掃 `Data` / `Result` 之類的常見 key，找不到就回傳整個 result 陣列。
     *
     * @return list<array<string,mixed>>
     */
    public function items(): array
    {
        foreach (['Data', 'SearchData', 'Result', 'Items'] as $key) {
            $candidate = $this->result[$key] ?? null;
            if (is_array($candidate)) {
                return $this->normalizeList($candidate);
            }
        }

        // 沒有巢狀包裝：直接把 Result 視為單筆字軌物件
        if ($this->result === []) {
            return [];
        }

        return [$this->result];
    }

    /**
     * 多筆字軌情境下，便捷取出第一筆的 ManagementNo（用於最常見的「依條件搜得唯一一筆」情境）。
     */
    public function firstManagementNo(): ?string
    {
        $items = $this->items();
        if ($items === []) {
            return null;
        }
        $first = $items[0];
        $value = $first['ManagementNo'] ?? null;
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * 把可能是「單筆物件」或「list of 物件」的陣列統一成 list 形式。
     *
     * @param array<int|string,mixed> $candidate
     * @return list<array<string,mixed>>
     */
    private function normalizeList(array $candidate): array
    {
        if ($candidate === []) {
            return [];
        }

        // 判斷是否為 list（int keys 0..n-1）
        if (array_is_list($candidate)) {
            $list = [];
            foreach ($candidate as $row) {
                if (is_array($row)) {
                    /** @var array<string,mixed> $row */
                    $list[] = $row;
                }
            }
            return $list;
        }

        /** @var array<string,mixed> $candidate */
        return [$candidate];
    }
}
