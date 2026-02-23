@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}" />
@endsection

@section('content')
<main class="staff-index">
    <div class="staff-index__inner">
        <h1 class="staff-index__title">
            <span class="staff-index__title-bar"></span>
            スタッフ一覧
        </h1>

        {{-- 一覧テーブル --}}
        <div class="staff-table">
            <table class="staff-table__table">
                <thead class="staff-table__head">
                    <tr class="staff-table__row">
                        <th class="staff-table__th">名前</th>
                        <th class="staff-table__th">メールアドレス</th>
                        <th class="staff-table__th">月次勤怠</th>
                    </tr>
                </thead>

                <tbody class="staff-table__body">
                    @forelse ($users as $a)
                    <tr class="staff-table__row">
                        <td class="staff-table__td">{{ $a->name }}</td>
                        <td class="staff-table__td">{{ $a->email ?? '' }}</td>
                        <td class="staff-table__td">
                            <a class="staff-table__detail" href="{{ route('admin.attendance_detail.show', $a->id) }}">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr class="staff-table__row">
                        <td class="staff-table__td staff-table__empty" colspan="6">
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