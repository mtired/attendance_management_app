@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/request_detail.css') }}" />
@endsection

@section('content')
    <main class="attendance-detail">
        <div class="attendance-detail__inner">

            <h1 class="attendance-detail__title">
                <span class="attendance-detail__title-bar"></span>
                勤怠詳細
            </h1>
            <form class="staff-attendance-list__form"
                action="{{ route('admin.request_detail.approve', ['attendanceChangeRequest' => $attendance->id]) }}"
                method="post">
                @csrf
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
                                    {{-- 申請後：テキスト表示（入力不可） --}}
                                    @php
                                        $clockIn =
                                            $hasRequest && $displayRequest->proposed_clock_in_at
                                                ? \Carbon\Carbon::parse($displayRequest->proposed_clock_in_at)->format(
                                                    'H:i',
                                                )
                                                : '—';

                                        $clockOut =
                                            $hasRequest && $displayRequest->proposed_clock_out_at
                                                ? \Carbon\Carbon::parse($displayRequest->proposed_clock_out_at)->format(
                                                    'H:i',
                                                )
                                                : '—';
                                    @endphp

                                    <div class="time-range time-range--text">
                                        <span class="time-text">{{ $clockIn }}</span>
                                        <span class="time-range__sep">〜</span>
                                        <span class="time-text">{{ $clockOut }}</span>
                                    </div>
                                </td>
                            </tr>

                            {{-- 休憩 --}}
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

                            {{-- 備考 --}}
                            <tr class="detail-table__row">
                                <th class="detail-table__th">備考</th>
                                <td class="detail-table__td">
                                    <p class="remarks-text">
                                        {{ $hasRequest ? $displayRequest->remarks ?? '—' : '—' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <div class="attendance-detail__action-form">
                    @if ($isPending)
                        <button class="attendance-detail__actions" type="submit">承認</button>
                    @else
                        <button class="attendance-detail__actions" type="button" disabled>承認済み</button>
                    @endif
                </div>
            </form>
        </div>
    </main>
@endsection
