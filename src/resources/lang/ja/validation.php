<?php

return [

/*** バリデーションエラーの共通メッセージ定義 ***/

    'required' => ':attributeを入力してください',
    'email' => ':attributeはメール形式で入力してください',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'max' => [
        'string' => ':attributeは:max文字以下で入力してください',
    ],
    'unique' => 'この:attributeは既に使われています',


/*** 特定フィールド × ルールの個別指定 ***/
    'custom' => [
        'password.confirmed' => ':attributeと一致しません'
    ],

/*** フィールド名を日本語に変換 ***/
    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード',
    ],
];