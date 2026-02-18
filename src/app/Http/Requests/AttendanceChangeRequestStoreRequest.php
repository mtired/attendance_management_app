<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceChangeRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'integer', 'exists:attendances,id'],

            'requested_clock_in_at'  => ['nullable', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'requested_clock_out_at' => ['nullable', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'requested_breaks.*.start' => ['nullable', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'requested_breaks.*.end'   => ['nullable', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'requested_breaks' => ['array'],
            'requested_breaks.*.target_break_id' => ['nullable', 'integer'],

            'requested_remarks' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_clock_in_at.regex'  => '出勤時間は h:mm 形式で入力してください',
            'requested_clock_out_at.regex' => '退勤時間は h:mm 形式で入力してください',
            'requested_breaks.*.start.regex' => '休憩開始時間は h:mm 形式で入力してください',
            'requested_breaks.*.end.regex'   => '休憩終了時間は h:mm 形式で入力してください',
            'requested_remarks.required' => '備考を記入してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $in  = $this->input('requested_clock_in_at');
            $out = $this->input('requested_clock_out_at');

            $inMin  = $this->toMinutesOrNull($in);
            $outMin = $this->toMinutesOrNull($out);

            // 出勤時間が退勤時間より後になっている場合，および退勤時間が出勤時間より後になっている場合
            if ($inMin !== null && $outMin !== null && $inMin > $outMin) {
                $v->errors()->add('requested_clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('requested_breaks', []);
            foreach ($breaks as $i => $b) {
                $start = $b['start'] ?? null;
                $end   = $b['end'] ?? null;

                $startMin = $this->toMinutesOrNull($start);
                $endMin   = $this->toMinutesOrNull($end);

                if ($inMin !== null && $outMin !== null) {
                    // 休憩開始が勤務時間外（出勤前 / 退勤後）
                    if ($startMin !== null && ($startMin < $inMin || $startMin > $outMin)) {
                        $v->errors()->add("requested_breaks.$i.start", '休憩時間が不適切な値です');
                    }

                    // 休憩終了が退勤より後
                    if ($endMin !== null && $endMin > $outMin) {
                        $v->errors()->add("requested_breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            };
        });
    }

    private function toMinutesOrNull(?string $hhmm): ?int
    {
        if ($hhmm === null || $hhmm === '') {
            return null;
        }

        // h:mm (0-23):(00-59) だけ許可
        if (!preg_match('/^([0-1]?\d|2[0-3]):[0-5]\d$/', $hhmm)) {
            return null;
        }

        [$h, $m] = array_map('intval', explode(':', $hhmm, 2));
        return $h * 60 + $m;
    }
}
