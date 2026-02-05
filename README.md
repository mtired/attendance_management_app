# 勤怠アプリ

## 環境構築

### Docker ビルド

docker compose(v2)を使用してください

```bash
git clone https://github.com/mtired/attendance_management_app.git

cd attendance_management_app.
docker compose up -d --build

docker compose exec php bash

composer install

cp .env.example .env
# .env の環境変数を適宜変更してください。

php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

## 開発環境

ユーザー登録画面：
http://localhost/register

ログイン画面：
http://localhost/login

トップ画面：
http://localhost/

phpMyAdmin：
http://localhost:8080/

MailHog：
http://localhost:8025/

## 使用技術(実行環境)

Laravel：12.11.2

mysql：8.4

nginx：1.28

php：8.4.15

Composer：2.9.5

## ログインユーザ情報

ユーザ名：xxx
メールアドレス：xxx
パスワード：xxx

## 動作について

### ページ遷移について

#### XXXXX

- XXX

---

### その他の挙動について

#### XXXXX

- XXX

## PHP Unitテスト

テスト一括実施コマンド

```bash
php artisan test tests/Feature
```

## ER図
