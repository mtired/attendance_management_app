@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/verify_email.css') }}" />
@endsection

@section('content')
  <main class="verify-email">
    <div class="verify-email__inner">

      {{-- 完了通知 --}}
      <p class="verify-email__message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
      </p>

      {{-- 認証確認ボタン --}}
      <a class="verify-email__button" href="{{ config('services.mail_inbox_url') }}" target="_blank"
        rel="noopener noreferrer">
        認証はこちらから
      </a>

      {{-- 再送リンク（Laravel標準：verification.send） --}}
      <form class="verify-email__resend-form" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="verify-email__resend-link">
          認証メールを再送する
        </button>
      </form>

    </div>
  </main>
@endsection