@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff_attendance_list.css') }}" />
@endsection

@section('content')
    <main class="staff-attendance-index">
        <div class="staff-attendance-index__inner">
            <h1 class="staff-attendance-index__title">
                <span class="staff-attendance-index__title-bar"></span>
                {{ $user->name }} さんの勤怠
            </h1>
            <form class="staff-attendance-list__form"
                action="{{ route('admin.staff_attendance_list.csv', ['user' => $user->id]) }}" method="get">
                <input type="hidden" name="month" value="{{ request('month') ?? \Carbon\Carbon::now()->format('Y-m') }}">
                {{-- 月移動 --}}
                <div class="month-nav">
                    <a class="month-nav__side"
                        href="{{ route('admin.staff_attendance_list.index', ['user' => $user->id, 'month' => $prevMonth]) }}">
                        <span class="month-nav__arrow"><img src="{{ asset('images/image2.png') }}" alt="prev"
                                class="month-nav__arrow--prev" /></span> 前月
                    </a>

                    <div class="month-nav__center">
                        <span class="month-nav__icon" aria-hidden="true"><img src="{{ asset('images/image.png') }}"
                                alt="calender" class="month-nav__logo" /></span>
                        <span class="month-nav__month">{{ $currentMonth }}</span>
                    </div>

                    <a class="month-nav__side"
                        href="{{ route('admin.staff_attendance_list.index', ['user' => $user->id, 'month' => $nextMonth]) }}">
                        翌月 <span class="month-nav__arrow"><img src="{{ asset('images/image2.png') }}" alt="next"
                                class="month-nav__arrow--next" /></span>
                    </a>
                </div>

                {{-- 一覧テーブル --}}
                <div class="staff-attendance-table">
                    <table class="staff-attendance-table__table">
                        <thead class="staff-attendance-table__head">
                            <tr class="staff-attendance-table__row">
                                <th class="staff-attendance-table__th staff-attendance-table__th--date">日付</th>
                                <th class="staff-attendance-table__th">出勤</th>
                                <th class="staff-attendance-table__th">退勤</th>
                                <th class="staff-attendance-table__th">休憩</th>
                                <th class="staff-attendance-table__th">合計</th>
                                <th class="staff-attendance-table__th">詳細</th>
                            </tr>
                        </thead>

                        <tbody class="staff-attendance-table__body">
                            @forelse ($attendances as $a)
                                <tr class="staff-attendance-table__row">
                                    <td class="staff-attendance-table__td staff-attendance-table__td--date">
                                        {{ $a->work_date_label ?? $a->work_date }}
                                    </td>
                                    <td class="staff-attendance-table__td">{{ $a->clock_in ?? '' }}</td>
                                    <td class="staff-attendance-table__td">{{ $a->clock_out ?? '' }}</td>
                                    <td class="staff-attendance-table__td">{{ $a->break_time_label ?? '' }}</td>
                                    <td class="staff-attendance-table__td">{{ $a->total_time_label ?? '' }}</td>
                                    <td class="staff-attendance-table__td">
                                        @if ($a->id)
                                            <a class="staff-attendance-table__detail"
                                                href="{{ route('admin.attendance_detail.show', $a->id) }}">
                                                詳細
                                            </a>
                                        @else
                                            <p class="staff-attendance-table__detail">
                                                詳細
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="staff-attendance-table__row">
                                    <td class="staff-attendance-table__td staff-attendance-table__empty" colspan="6">
                                        表示するデータがありません
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="staff-attendance-list__action-form">
                    <button class="staff-attendance-list__actions" type="submit">
                        CSV出力
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection
