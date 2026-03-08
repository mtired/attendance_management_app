@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_detail.css') }}" />
@endsection

@section('content')
    <main class="attendance-detail">
        <div class="attendance-detail__inner">

            <h1 class="attendance-detail__title">
                <span class="attendance-detail__title-bar"></span>
                勤怠詳細
            </h1>
            <form class="staff-attendance-list__form" action="{{ route('admin.attendance.update') }}" method="post">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <section class="detail-card">
                    <table class="detail-table">
                        <tbody>
                            {{-- 名前 --}}
                            <tr class="detail-table__row">
                                <th class="detail-table__th">名前</th>
                                <td class="detail-table__td detail-table__td--split">
                                    <div class="detail-split detail-split--name">
                                        <span class="detail-split__item detail-split__item--strong">
                                            {{ $attendance->user->name }}
                                        </span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </td>
                            </tr>

                            {{-- 日付 --}}
                            <tr class="detail-table__row">
                                <th class="detail-table__th">日付</th>
                                <td class="detail-table__td detail-table__td--split">
                                    @php
                                        $workDate = isset($attendance->work_date)
                                            ? \Carbon\Carbon::parse($attendance->work_date)
                                            : null;
                                    @endphp

                                    @if ($workDate)
                                        <div class="detail-split">
                                            <span class="detail-split__item detail-split__item--strong">
                                                {{ $workDate->format('Y') }}年
                                            </span>
                                            <span></span> {{-- 〜分の空白 --}}
                                            <span class="detail-split__item detail-split__item--strong">
                                                {{ $workDate->format('n') }}月{{ $workDate->format('j') }}日
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            {{-- 出勤・退勤 --}}
                            <tr class="detail-table__row">
                                <th class="detail-table__th">出勤・退勤</th>
                                <td class="detail-table__td">
                                    @if ($hasPendingRequest)
                                        {{-- 申請後：テキスト表示（入力不可） --}}
                                        @php
                                            $clockIn = $pendingRequest->proposed_clock_in_at
                                                ? \Carbon\Carbon::parse($pendingRequest->proposed_clock_in_at)->format(
                                                    'H:i',
                                                )
                                                : '';
                                            $clockOut = $pendingRequest->proposed_clock_out_at
                                                ? \Carbon\Carbon::parse($pendingRequest->proposed_clock_out_at)->format(
                                                    'H:i',
                                                )
                                                : '';
                                        @endphp

                                        <div class="time-range time-range--text">
                                            <span class="time-text">{{ $clockIn ?: '—' }}</span>
                                            <span class="time-range__sep">〜</span>
                                            <span class="time-text">{{ $clockOut ?: '—' }}</span>
                                        </div>
                                    @else
                                        {{-- 申請前：入力可 --}}
                                        <div class="time-range">
                                            <input class="time-range__input" type="text" name="requested_clock_in_at"
                                                value="{{ $attendance->clock_in_at ? \Carbon\Carbon::parse($attendance->clock_in_at)->format('H:i') : '' }}"
                                                placeholder="h:mm">

                                            <span class="time-range__sep">〜</span>

                                            <input class="time-range__input" type="text" name="requested_clock_out_at"
                                                value="{{ $attendance->clock_out_at ? \Carbon\Carbon::parse($attendance->clock_out_at)->format('H:i') : '' }}"
                                                placeholder="h:mm">
                                        </div>
                                    @endif
                                </td>
                            </tr>

                            {{-- 休憩 --}}
                            @if ($hasPendingRequest)
                                {{-- 申請後：申請内容をテキスト表示 --}}
                                @foreach ($displayBreaks as $i => $b)
                                    @php
                                        $label = $i === 0 ? '休憩' : '休憩' . ($i + 1);
                                        $s = $b->start_at ? \Carbon\Carbon::parse($b->start_at)->format('H:i') : '';
                                        $e = $b->end_at ? \Carbon\Carbon::parse($b->end_at)->format('H:i') : '';
                                    @endphp

                                    <tr class="detail-table__row">
                                        <th class="detail-table__th">{{ $label }}</th>
                                        <td class="detail-table__td">
                                            <div class="time-range time-range--text">
                                                <span class="time-text">{{ $s ?: '—' }}</span>
                                                <span class="time-range__sep">〜</span>
                                                <span class="time-text">{{ $e ?: '—' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- 申請前：入力可（あなたの既存の@forでもOK） --}}
                                @for ($i = 0; $i <= $breaks->count(); $i++)
                                    @php
                                        $break = $breaks[$i] ?? null;
                                        $label = $i === 0 ? '休憩' : '休憩' . ($i + 1);
                                    @endphp

                                    <tr class="detail-table__row">
                                        <th class="detail-table__th">{{ $label }}</th>
                                        <td class="detail-table__td">
                                            <div class="time-range">
                                                @if ($break)
                                                    <input type="hidden"
                                                        name="requested_breaks[{{ $i }}][target_break_id]"
                                                        value="{{ $break->id }}">
                                                @endif

                                                <input class="time-range__input" type="text"
                                                    name="requested_breaks[{{ $i }}][start]"
                                                    value="{{ $break && $break->break_start_at ? \Carbon\Carbon::parse($break->break_start_at)->format('H:i') : '' }}"
                                                    placeholder="h:mm">

                                                <span class="time-range__sep">〜</span>

                                                <input class="time-range__input" type="text"
                                                    name="requested_breaks[{{ $i }}][end]"
                                                    value="{{ $break && $break->break_end_at ? \Carbon\Carbon::parse($break->break_end_at)->format('H:i') : '' }}"
                                                    placeholder="h:mm">
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                            @endif

                            {{-- 備考 --}}
                            <tr class="detail-table__row">
                                <th class="detail-table__th">備考</th>
                                <td class="detail-table__td detail-table__td--split">
                                    <div class="detail-split detail-split--remarks">
                                        <span class="detail-split__item detail-split__item--strong">
                                            @if ($hasPendingRequest)
                                                {!! nl2br(e($displayRequest->remarks ?? '—')) !!}
                                            @else
                                                <textarea class="detail-table__textarea" name="requested_remarks">{{ $attendance->remarks ?? '' }}</textarea>
                                            @endif
                                        </span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
                {{-- テーブルの下にエラーまとめ表示 --}}
                @if ($errors->any())
                    <div class="form-errors" role="alert" aria-live="polite">
                        <ul class="form-errors__list">
                            @foreach ($errors->all() as $error)
                                <li class="form-errors__item">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="attendance-detail__action-form">
                    @if ($hasPendingRequest)
                        <p class="form-info form-info--disabled">
                            承認待ちのため修正はできません。
                        </p>
                    @else
                        <button class="attendance-detail__actions" type="submit">
                            修正
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </main>
@endsection
