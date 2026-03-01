<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>勤怠アプリ</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

@yield('js')

<body>
    <header class="header">
        <div class="header__inner">
            {{-- ロゴ --}}
            @auth('admin')
                <a href="{{ route('admin.attendance_list.index') }}" class="header__logo-link">
                    <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH" class="header__logo" />
                </a>
            @elseauth('web')
                <a href="{{ route('attendance.index') }}" class="header__logo-link">
                    <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH" class="header__logo" />
                </a>
            @else
                {{-- 未ログイン時 --}}
                <a href="{{ route('login') }}" class="header__logo-link">
                    <img src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH" class="header__logo" />
                </a>
            @endauth

            @unless (request()->is('login') || request()->is('register'))
                {{-- 右側メニュー --}}
                <nav class="header__nav">
                    {{-- 一般ユーザーでログイン中 --}}
                    @auth('web')
                        <a href="{{ route('attendance.index') }}" class="header__nav-text">勤怠</a>
                        <a href="{{ route('attendance_list.index') }}" class="header__nav-text">勤怠一覧</a>
                        <a href="{{ route('attendance_change_request.index') }}" class="header__nav-text">申請</a>
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button class="header__nav-text" type="submit">
                                ログアウト
                            </button>
                        </form>
                    @endauth

                    {{-- 管理者でログイン中 --}}
                    @auth('admin')
                        <a href="{{ route('admin.attendance_list.index') }}" class="header__nav-text">勤怠一覧</a>
                        <a href="{{ route('admin.staff_list.index') }}" class="header__nav-text">スタッフ一覧</a>
                        <a href="/stamp_correction_request/list" class="header__nav-text">申請一覧</a>
                        <form action="{{ route('admin.logout') }}" method="post">
                            @csrf
                            <button class="header__nav-text" type="submit">
                                ログアウト
                            </button>
                        </form>
                    @endauth

                    {{-- どっちも未ログイン --}}
                    @guest('web')
                        @guest('admin')
                            <a href="/login" class="header__nav-text">ログイン</a>
                        @endguest
                    @endguest
                </nav>
            @endunless
        </div>
    </header>

    <main>@yield('content')</main>
</body>

</html>
