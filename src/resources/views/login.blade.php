@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/login.css') }}" />
@endsection

@section('content')
  <main class="login">
    <div class="login__inner">
      <h1 class="login__title">ログイン</h1>
      <form class="login__form" action="{{ route('login') }}" method="post" novalidate>
        @csrf

        <div class="login__group">
          <label class="login__label" for="email">メールアドレス</label>
          <input class="login__input" id="email" name="email" type="text" value="{{ old('email') }}" />
          @error('email')
            <p class="form-error">{{ $message }}</p>
          @enderror
        </div>

        <div class="login__group">
          <label class="login__label" for="password">パスワード</label>
          <input class="login__input" id="password" name="password" type="password" />
          @error('password')
            <p class="form-error">{{ $message }}</p>
          @enderror
        </div>

        <div class="login__actions">
          <button class="login__button" type="submit">ログインする</button>
        </div>

        <div class="login__register">
          <a class="login__register-link" href="/register">会員登録はこちら</a>
        </div>
      </form>
    </div>
  </main>
@endsection
