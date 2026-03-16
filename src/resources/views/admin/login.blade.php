@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}" />
@endsection

@section('content')
  <main class="login">
    <div class="login__inner">
      <h1 class="login__title">管理者ログイン</h1>
      <form class="login__form" action="{{ route('admin.login.store') }}" method="post" novalidate>
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
          <button class="login__button" type="submit">管理者ログインする</button>
        </div>
      </form>
    </div>
  </main>
@endsection
