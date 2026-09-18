<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\CycleMonth;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CycleController extends Controller
{
    private function nextAutoCode(): string
    {
        $max = 0;
        $codes = Cycle::query()->pluck('code');

        foreach ($codes as $code) {
            $code = (string) $code;
            if (preg_match('/Q(\d+)$/', $code, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return 'CodeQ' . ($max + 1);
    }

    protected function historyExistsForCycle(int $cycleId): bool
    {
        return false;
    }

    protected function syncCurrentDataToCycle(int $cycleId): void
    {
    }

    protected function archiveCycleData(int $cycleId): void
    {
    }

    protected function resetWorkingDataForNewCycle(int $cycleId): void
    {
    }

    protected function restoreWorkingDataFromHistory(int $cycleId): void
    {
    }

    protected function clearWorkingDataAfterClose(): void
    {
    }

    private function closeOpenMonthsForCycle(int $cycleId): void
    {
        CycleMonth::query()
            ->where('cycle_id', $cycleId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    private function parkCurrentActiveCycle(?int $exceptCycleId = null): void
    {
        $active = Cycle::query()
            ->where('is_active', true)
            ->when($exceptCycleId, fn ($q) => $q->where('id', '!=', $exceptCycleId))
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (! $active) {
            return;
        }

        $this->syncCurrentDataToCycle((int) $active->id);
        $this->archiveCycleData((int) $active->id);
        $this->closeOpenMonthsForCycle((int) $active->id);

        $active->update([
            'status' => Cycle::STATUS_CLOSED,
            'is_active' => false,
            'closed_at' => now(),
        ]);
    }

    public function index(Request $request)
    {
        $cycles = Cycle::query()
            ->with(['months' => fn ($q) => $q->orderBy('month_no')])
            ->orderByDesc('id')
            ->paginate(10);
        $activeCycle = Cycle::active();
        $allCycles = Cycle::query()->orderByDesc('id')->get(['id', 'name', 'code']);

        $fallbackCycleId = $activeCycle?->id
            ?? ($allCycles->first()->id ?? null);

        $requestedMonthCycleId = (int) ($request->query('month_cycle_id', $fallbackCycleId) ?? 0);
        $selectedMonthCycle = $requestedMonthCycleId > 0
            ? Cycle::query()->find($requestedMonthCycleId)
            : null;

        if (! $selectedMonthCycle && $fallbackCycleId) {
            $selectedMonthCycle = Cycle::query()->find((int) $fallbackCycleId);
        }

        $selectedMonthCycleId = $selectedMonthCycle?->id;

        $monthOpenAt = array_fill(1, 12, null);
        if ($selectedMonthCycle) {
            $monthRows = CycleMonth::query()
                ->where('cycle_id', $selectedMonthCycle->id)
                ->get()
                ->keyBy('month_no');

            for ($month = 1; $month <= 12; $month++) {
                $openAt = $monthRows->get($month)?->open_at;
                $monthOpenAt[$month] = $openAt ? $openAt->format('d/m/Y') : null;
            }
        }

        return view('admin-cycle', [
            'cycles' => $cycles,
            'activeCycle' => $activeCycle,
            'allCycles' => $allCycles,
            'selectedMonthCycle' => $selectedMonthCycle,
            'selectedMonthCycleId' => $selectedMonthCycle?->id,
            'monthOpenAt' => $monthOpenAt,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string'],
        ]);

        $cycle = null;
        $data['name'] = PlainTextNormalizer::normalize($data['name']);
        $data['note'] = PlainTextNormalizer::nullable($data['note'] ?? null);

        DB::transaction(function () use ($data, &$cycle) {
            $this->parkCurrentActiveCycle();

            $cycle = Cycle::query()->create([
                'name' => $data['name'],
                'code' => $this->nextAutoCode(),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => Cycle::STATUS_OPEN,
                'is_active' => true,
                'opened_at' => now(),
                'closed_at' => null,
                'note' => $data['note'] ?? null,
            ]);

            $this->resetWorkingDataForNewCycle((int) $cycle->id);
        });

        $this->sendCycleNotification('cycle_opened', (string) $cycle->name);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'cycle' => [
                    'id' => $cycle->id,
                ],
            ]);
        }

        return back()->with('success', request('lang') === 'th' ? 'บันทึกรอบแล้ว' : 'Cycle saved.');
    }

    public function activate(Cycle $cycle, Request $request)
    {
        $cycleId = (int) $cycle->id;
        $shouldNotify = false;
        $cycleName = '';

        DB::transaction(function () use ($cycleId, &$shouldNotify, &$cycleName) {
            $target = Cycle::query()->whereKey($cycleId)->lockForUpdate()->firstOrFail();

            if ((bool) $target->is_active) {
                if ((string) $target->status !== Cycle::STATUS_OPEN) {
                    $target->update([
                        'status' => Cycle::STATUS_OPEN,
                        'closed_at' => null,
                    ]);
                }
                return;
            }

            $this->parkCurrentActiveCycle($cycleId);

            $target->update([
                'status' => Cycle::STATUS_OPEN,
                'is_active' => true,
                'opened_at' => $target->opened_at ?: now(),
                'closed_at' => null,
            ]);

            if ($this->historyExistsForCycle($cycleId)) {
                $this->restoreWorkingDataFromHistory($cycleId);
            } else {
                $this->resetWorkingDataForNewCycle($cycleId);
            }

            $cycleName = (string) $target->name;
            $shouldNotify = true;
        });

        if ($shouldNotify) {
            $this->sendCycleNotification('cycle_opened', $cycleName);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'cycle' => [
                    'id' => $cycleId,
                ],
            ]);
        }

        return back()->with('success', request('lang') === 'th' ? 'เปิดใช้งานรอบแล้ว' : 'Cycle activated.');
    }

    public function close(Cycle $cycle, Request $request)
    {
        $cycleId = (int) $cycle->id;
        $cycleName = '';

        try {
            DB::transaction(function () use ($cycleId, &$cycleName) {
                $target = Cycle::query()->whereKey($cycleId)->lockForUpdate()->firstOrFail();

                if (! (bool) $target->is_active) {
                    throw new \RuntimeException('Only active cycle can be closed.');
                }

                $this->syncCurrentDataToCycle($cycleId);
                $this->archiveCycleData($cycleId);
                $this->clearWorkingDataAfterClose();
                $this->closeOpenMonthsForCycle($cycleId);

                $cycleName = (string) $target->name;
                $target->update([
                    'status' => Cycle::STATUS_CLOSED,
                    'is_active' => false,
                    'closed_at' => now(),
                ]);
            });

            $this->sendCycleNotification('cycle_closed', $cycleName);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => request('lang') === 'th' ? 'ปิดรอบไม่สำเร็จ' : 'Close failed.',
                ], 422);
            }

            return back()->with('error', request('lang') === 'th' ? 'ปิดรอบไม่สำเร็จ' : 'Close failed.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', request('lang') === 'th' ? 'ปิดรอบแล้ว' : 'Cycle closed.');
    }

    public function saveMonths(Request $request)
    {
        $rules = [
            'cycle_id' => ['required', 'integer', 'exists:cycles,id'],
            'months' => ['required', 'array'],
        ];

        for ($month = 1; $month <= 12; $month++) {
            $rules["months.$month.open_at"] = ['nullable', 'string', 'max:20'];
        }

        $validated = $request->validate($rules);
        $cycleId = (int) $validated['cycle_id'];

        DB::transaction(function () use ($request, $cycleId) {
            for ($month = 1; $month <= 12; $month++) {
                $rawOpenAt = trim((string) $request->input("months.$month.open_at", ''));

                if ($rawOpenAt === '') {
                    CycleMonth::query()
                        ->where('cycle_id', $cycleId)
                        ->where('month_no', $month)
                        ->delete();
                    continue;
                }

                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawOpenAt, $matchesDmy)) {
                    $day = (int) $matchesDmy[1];
                    $monthNo = (int) $matchesDmy[2];
                    $year = (int) $matchesDmy[3];

                    $valueToSave = checkdate($monthNo, $day, $year)
                        ? sprintf('%04d-%02d-%02d 00:00:00', $year, $monthNo, $day)
                        : $rawOpenAt;
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawOpenAt)) {
                    $valueToSave = $rawOpenAt . ' 00:00:00';
                } else {
                    $valueToSave = $rawOpenAt;
                }

                CycleMonth::query()->updateOrCreate(
                    [
                        'cycle_id' => $cycleId,
                        'month_no' => $month,
                    ],
                    [
                        'open_at' => $valueToSave,
                    ]
                );
            }
        });

        return back()->with(
            'success',
            request('lang') === 'th'
                ? 'บันทึกวันเวลาเปิดฟอร์ม KPI รายเดือนเรียบร้อยแล้ว'
                : 'Monthly KPI form open dates saved successfully.'
        );
    }

    public function toggleMonth(Cycle $cycle, int $month, Request $request)
    {
        if ($month < 1 || $month > 12) {
            return back()->with('error', request('lang') === 'th' ? 'เดือนที่เลือกไม่ถูกต้อง' : 'Invalid month.');
        }

        $monthRow = CycleMonth::query()
            ->where('cycle_id', $cycle->id)
            ->where('month_no', $month)
            ->first();

        if (! $monthRow) {
            return back()->with('error', request('lang') === 'th' ? 'ไม่พบข้อมูลเดือนของรอบนี้' : 'Month schedule was not found.');
        }

        $requestedState = $request->input('is_active');
        $nextState = $requestedState === null
            ? ! (bool) $monthRow->is_active
            : in_array(strtolower((string) $requestedState), ['1', 'true', 'yes', 'on'], true);

        if ($nextState) {
            $isCycleActive = Cycle::query()
                ->whereKey($cycle->id)
                ->where('is_active', true)
                ->exists();

            if (! $isCycleActive) {
                return back()->with(
                    'error',
                    request('lang') === 'th'
                        ? 'กรุณาเปิดรอบก่อน จึงจะเปิดรอบรายเดือนได้'
                        : 'Please activate cycle before opening monthly schedule.'
                );
            }
        }

        $monthRow->update([
            'is_active' => $nextState,
        ]);

        $this->sendCycleNotification(
            $nextState ? 'month_opened' : 'month_closed',
            (string) $cycle->name,
            $month
        );

        return back()->with(
            'success',
            request('lang') === 'th'
                ? ($nextState ? 'เปิดรอบรายเดือนแล้ว' : 'ปิดรอบรายเดือนแล้ว')
                : ($nextState ? 'Monthly cycle opened.' : 'Monthly cycle closed.')
        );
    }

    private function thaiMonthName(int $month): string
    {
        $names = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
            4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
            10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        return $names[$month] ?? "เดือน {$month}";
    }

    private function sendCycleNotification(string $action, string $cycleName, int $monthNo = 0): void
    {
        $adminUserId = (int) auth()->id();
        $thaiMonth = ($monthNo >= 1 && $monthNo <= 12) ? $this->thaiMonthName($monthNo) : '';

        switch ($action) {
            case 'cycle_opened':
                $title = 'เปิดรอบการประเมิน KPI';
                $message = "ผู้ดูแลระบบได้เปิดรอบการประเมิน \"{$cycleName}\" แล้ว — สามารถเริ่มบันทึกข้อมูล KPI ได้เลย";
                break;
            case 'cycle_closed':
                $title = 'ปิดรอบการประเมิน KPI';
                $message = "ผู้ดูแลระบบได้ปิดรอบการประเมิน \"{$cycleName}\" เรียบร้อยแล้ว";
                break;
            case 'month_opened':
                $title = "เปิดรอบบันทึก KPI — เดือน{$thaiMonth}";
                $message = "ผู้ดูแลระบบได้เปิดให้บันทึก KPI ประจำเดือน{$thaiMonth} ในรอบ \"{$cycleName}\" แล้ว — กรุณาดำเนินการภายในกำหนด";
                break;
            case 'month_closed':
                $title = "ปิดรอบบันทึก KPI — เดือน{$thaiMonth}";
                $message = "ผู้ดูแลระบบได้ปิดการบันทึก KPI ประจำเดือน{$thaiMonth} ในรอบ \"{$cycleName}\" เรียบร้อยแล้ว";
                break;
            default:
                return;
        }

        $payload = json_encode([
            'action' => $action,
            'cycle_name' => $cycleName,
            'month_no' => $monthNo,
            'month_name' => $thaiMonth,
        ]);

        $recipientIds = AppUser::query()
            ->when($adminUserId > 0, fn ($q) => $q->where('id', '!=', $adminUserId))
            ->pluck('id')
            ->filter(fn ($id) => (int) $id > 0)
            ->all();

        if ($recipientIds === []) {
            return;
        }

        $now = now()->toDateTimeString();
        $rows = [];
        foreach ($recipientIds as $uid) {
            $rows[] = [
                'recipient_user_id' => (int) $uid,
                'actor_user_id'     => $adminUserId ?: null,
                'type'              => 'cycle_update',
                'title'             => $title,
                'message'           => $message,
                'link_url'          => null,
                'payload_json'      => $payload,
                'is_read'           => false,
                'read_at'           => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        DB::table('app_notifications')->insert($rows);
    }
}
