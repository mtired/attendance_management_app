@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/register_attendance.css') }}" />
@endsection

@section('content')
    <main class="register-attendance">
        <div class="register-attendance__inner">
            {{-- ステータス --}}
            <div class="attendance__badge">{{ $status }}</div>

            {{-- 日付 --}}
            <p class="attendance__date" id="now-date"></p>

            {{-- 時刻 --}}
            <p class="attendance__time" id="now-time"></p>

            {{-- ボタン --}}
            <div class="attendance__actions">
                @if ($status === '勤務外')
                    <form action="{{ route('attendance.clock_in') }}" method="post" class="attendance__form">
                        @csrf
                        <button type="submit" class="attendance__btn attendance__btn--black">
                            出勤
                        </button>
                    </form>
                @elseif($status === '出勤中')
                    <form action="{{ route('attendance.clock_out') }}" method="post" class="attendance__form">
                        @csrf
                        <button type="submit" class="attendance__btn attendance__btn--black">
                            退勤
                        </button>
                    </form>

                    <form action="{{ route('attendance.break_start') }}" method="post" class="attendance__form">
                        @csrf
                        <button type="submit" class="attendance__btn attendance__btn--white">
                            休憩入
                        </button>
                    </form>
                @elseif($status === '休憩中')
                    <form action="{{ route('attendance.break_end') }}" method="post" class="attendance__form">
                        @csrf
                        <button type="submit" class="attendance__btn attendance__btn--white">
                            休憩戻
                        </button>
                    </form>
                @elseif($status === '退勤済')
                    <form action="route('attendance.clock_out')" method="post" class="attendance__form">
                        @csrf
                        <text class="attendance__txt ">
                            お疲れ様でした。
                        </text>
                    </form>
                @endif
            </div>
        </div>
    </main>
@endsection


@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const serverTimeStr = @json($serverNow->toIso8601String());
            const serverTime = new Date(serverTimeStr);
            const clientLoadTime = new Date();
            const diff = serverTime - clientLoadTime;

            const pad2 = (n) => String(n).padStart(2, '0');

            function updateClock() {
                const now = new Date(Date.now() + diff);

                const week = ['日', '月', '火', '水', '木', '金', '土'][now.getDay()];
                const y = now.getFullYear();
                const m = pad2(now.getMonth() + 1);
                const d = pad2(now.getDate());
                const hh = pad2(now.getHours());
                const mm = pad2(now.getMinutes());

                const dateEl = document.getElementById('now-date');
                const timeEl = document.getElementById('now-time');
                if (!dateEl || !timeEl) return; // 要素なければ何もしない

                dateEl.textContent = `${y}年${m}月${d}日(${week})`;
                timeEl.textContent = `${hh}:${mm}`;
            }

            updateClock();
            setInterval(updateClock, 1000);
        });
    </script>
@endsection
