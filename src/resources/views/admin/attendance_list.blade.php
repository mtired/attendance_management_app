@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}" />
@endsection

@section('content')
<main class="attendance-index">
    <div class="attendance-index__inner">
        <h1 class="attendance-index__title">
            <span class="attendance-index__title-bar"></span>
            勤怠一覧
        </h1>

        {{-- 日付移動（※変数名は prevMonth/nextMonth のままでOK） --}}
        <div class="month-nav">
            <a class="month-nav__side" href="{{ route('admin.attendance_list.index', ['month' => $prevMonth]) }}">
                <span class="month-nav__arrow">←</span> 前日
            </a>

            <div class="month-nav__center">
                <span class="month-nav__icon" aria-hidden="true">🗓</span>
                <span class="month-nav__month">{{ $currentMonth }}</span>
            </div>

            <a class="month-nav__side" href="{{ route('admin.attendance_list.index', ['month' => $nextMonth]) }}">
                翌日 <span class="month-nav__arrow">→</span>
            </a>
        </div>

        {{-- 一覧テーブル --}}
        <div class="attendance-table">
            <table class="attendance-table__table">
                <thead class="attendance-table__head">
                    <tr class="attendance-table__row">
                        <th class="attendance-table__th">名前</th>
                        <th class="attendance-table__th">出勤</th>
                        <th class="attendance-table__th">退勤</th>
                        <th class="attendance-table__th">休憩</th>
                        <th class="attendance-table__th">合計</th>
                        <th class="attendance-table__th">詳細</th>
                    </tr>
                </thead>

                <tbody class="attendance-table__body">
                    @forelse ($attendances as $a)
                    <tr class="attendance-table__row">
                        <td class="attendance-table__td">{{ $a->name }}</td>
                        <td class="attendance-table__td">{{ $a->clock_in ?? '' }}</td>
                        <td class="attendance-table__td">{{ $a->clock_out ?? '' }}</td>
                        <td class="attendance-table__td">{{ $a->break_time_label ?? '' }}</td>
                        <td class="attendance-table__td">{{ $a->total_time_label ?? '' }}</td>
                        <td class="attendance-table__td">
                            <a class="attendance-table__detail" href="{{ route('admin.attendance_detail.show', $a->id) }}">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr class="attendance-table__row">
                        <td class="attendance-table__td attendance-table__empty" colspan="6">
                            表示するデータがありません
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</main>
@endsection