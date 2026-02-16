<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceChangeRequestStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'integer', 'exists:attendances,id'],

            'requested_clock_in_at' => ['nullable', 'date_format:H:i'],
            'requested_clock_out_at' => ['nullable', 'date_format:H:i'],

            'requested_breaks' => ['array'],
            'requested_breaks.*.target_break_id' => ['nullable', 'integer'],
            'requested_breaks.*.start' => ['nullable', 'date_format:H:i'],
            'requested_breaks.*.end'   => ['nullable', 'date_format:H:i'],

            'requested_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $in  = $this->input('requested_clock_in_at');
            $out = $this->input('requested_clock_out_at');

            // 出勤と退勤はセットで入れる、などルールがあるならここで
            // if ($in && !$out) { ... } みたいにも書ける

            // 出勤 > 退勤 を弾く
            if ($in && $out && $this->toMinutes($in) > $this->toMinutes($out)) {
                $v->errors()->add('requested_clock_out_at', '退勤時間は出勤時間より後にしてください。');
            }

            // 休憩の整合性：開始/終了どちらか片方だけを弾く + 開始 > 終了 を弾く
            $breaks = $this->input('requested_breaks', []);
            foreach ($breaks as $i => $b) {
                $start = $b['start'] ?? null;
                $end   = $b['end'] ?? null;

                // 空行はOK
                if (!$start && !$end) {
                    continue;
                }

                // 片方だけ入力はNG
                if (($start && !$end) || (!$start && $end)) {
                    $v->errors()->add("requested_breaks.$i.end", '休憩は開始・終了をセットで入力してください。');
                    continue;
                }

                // 開始 > 終了 はNG
                if ($start && $end && $this->toMinutes($start) >= $this->toMinutes($end)) {
                    $v->errors()->add("requested_breaks.$i.end", '休憩終了は休憩開始より後にしてください。');
                }

                // 休憩が勤務時間の外に出るのを弾きたいなら（出退勤がある時だけ）
                if ($in && $out && $start && $end) {
                    if ($this->toMinutes($start) < $this->toMinutes($in) || $this->toMinutes($end) > $this->toMinutes($out)) {
                        $v->errors()->add("requested_breaks.$i.start", '休憩は勤務時間内に収めてください。');
                    }
                }
            }
        });
    }

    private function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }
}
