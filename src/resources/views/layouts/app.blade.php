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
                <a href="/attendance" class="header__logo-link">
                    <img
                        src="{{ asset('images/COACHTECHヘッダーロゴ.png') }}"
                        alt="COACHTECH"
                        class="header__logo"
                    />
                </a>

                @unless (request()->is('login') || request()->is('register'))
                {{-- 右側メニュー --}}
                <nav class="header__nav">
                    {{-- 一般ユーザーでログイン中 --}}
                    @auth('web')
                    <a href="/attendance" class="header__nav-text">勤怠</a>
                    <a href="/attendance/list" class="header__nav-text"
                        >勤怠一覧</a
                    >
                    <a
                        href="/stamp_correction_request/list"
                        class="header__nav-text"
                        >申請</a
                    >
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="header__nav-text" type="submit">
                            ログアウト
                        </button>
                    </form>
                    @endauth

                    {{-- 管理者でログイン中 --}}
                    @auth('admin')
                    <a href="admin/attendance/list" class="header__nav-text"
                        >勤怠一覧</a
                    >
                    <a href="/admin/staff/list" class="header__nav-text"
                        >スタッフ一覧</a
                    >
                    <a
                        href="/stamp_correction_request/list"
                        class="header__nav-text"
                        >申請一覧</a
                    >
                    <form action="{{ route('admin.logout') }}" method="post">
                        @csrf
                        <button class="header__nav-text" type="submit">
                            ログアウト
                        </button>
                    </form>
                    @endauth

                    {{-- どっちも未ログイン --}}
                    @guest('web') @guest('admin')
                    <a href="/login" class="header__nav-text">ログイン</a>
                    @endguest @endguest
                </nav>
                @endunless
            </div>
        </header>

        <main>@yield('content')</main>
    </body>
</html>
