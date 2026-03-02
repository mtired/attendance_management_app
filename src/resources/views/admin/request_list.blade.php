@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
    @php
        $activeTab = request('tab', 'pending');
    @endphp

    <main class="request-list">
        <div class="request-list__inner">

            <h1 class="request-list__title">
                <span class="request-list__title-bar"></span>
                申請一覧
            </h1>

            <div class="request-list__tabs">
                <a href="{{ route('admin.attendance_change_request.index', ['tab' => 'pending']) }}"
                    class="request-list__tab {{ $activeTab === 'pending' ? 'is-active' : '' }}">
                    承認待ち
                </a>

                <a href="{{ route('admin.attendance_change_request.index', ['tab' => 'approved']) }}"
                    class="request-list__tab {{ $activeTab === 'approved' ? 'is-active' : '' }}">
                    承認済み
                </a>
            </div>

            <div class="request-list__tabs-line"></div>

            {{-- 承認待ち --}}
            <section class="request-list__panel {{ $activeTab === 'pending' ? 'is-active' : '' }}" role="tabpanel">
                <div class="request-list__table-wrap">
                    <table class="request-list__table">
                        <thead>
                            <tr>
                                <th>状態</th>
                                <th>名前</th>
                                <th>対象日時</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingRequests as $req)
                                <tr>
                                    <td class="request-list__status">承認待ち</td>

                                    {{-- requested_by のユーザー名（リレーション推奨） --}}
                                    <td>{{ $req->requestedBy?->name ?? '—' }}</td>

                                    {{-- 提案出勤〜退勤 --}}
                                    <td>
                                        {{ optional($req->proposed_clock_in_at)->format('Y/m/d') ?? '—' }}
                                    </td>

                                    {{-- remarks --}}
                                    <td>{{ $req->remarks ?: '—' }}</td>

                                    {{-- created_at --}}
                                    <td>{{ optional($req->created_at)->format('Y/m/d') ?? '—' }}</td>

                                    <td class="request-list__detail">
                                        <a class="request-list__detail-link"
                                            href="{{ route('admin.attendance_detail.show', ['attendance' => $req->attendance_id]) }}">
                                            詳細
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="request-list__empty" colspan="6">承認待ちの申請はありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- 承認済み --}}
            <section class="request-list__panel {{ $activeTab === 'approved' ? 'is-active' : '' }}" role="tabpanel">
                <div class="request-list__table-wrap">
                    <table class="request-list__table">
                        <thead>
                            <tr>
                                <th>状態</th>
                                <th>名前</th>
                                <th>対象日時</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($approvedRequests as $req)
                                <tr>
                                    <td class="request-list__status">承認済み</td>
                                    <td>{{ $req->requestedBy?->name ?? '—' }}</td>

                                    <td>
                                        {{ optional($req->proposed_clock_in_at)->format('Y/m/d') ?? '—' }}
                                    </td>

                                    <td>{{ $req->remarks ?: '—' }}</td>
                                    <td>{{ optional($req->created_at)->format('Y/m/d') ?? '—' }}</td>

                                    <td class="request-list__detail">
                                        <a class="request-list__detail-link"
                                            href="{{ route('attendance_detail.show', ['attendance' => $req->attendance_id]) }}">
                                            詳細
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="request-list__empty" colspan="6">承認済みの申請はありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>
@endsection
