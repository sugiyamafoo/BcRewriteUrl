# BcRewriteUrl

ブログURLリライト（Rewrite blog URL plugin for baserCMS）

baserCMS 5 向けプラグインです。
ブログ記事URLから `/archives/` を取り除き、`/archives/` 付きURLへのアクセスを 301 リダイレクトで新URLへ転送します。

| 種類 | URL |
| --- | --- |
| 旧URL | `/blog/archives/123` |
| 新URL | `/blog/123` |

## 機能

- ブログ記事URLから `/archives/` を取り除いて表示します。
- 旧URL（`/archives/` 付き）へのアクセスを 301 で新URLへ転送します。クエリ文字列は引き継ぎます。
- ページ内のリンク（HTML / XML）に含まれる `/archives/` を自動で取り除きます。
- 複数のブログがある場合も、すべてのブログを対象にします。

## 動作要件

- baserCMS 5.x（BcBlog プラグインが有効であること）
- PHP 8.0 以上

## インストール

1. このディレクトリを `/plugins/BcRewriteUrl` として配置します。
2. 管理システムのプラグイン管理にアクセスし、表示されている「ブログURLリライト」プラグインをインストール（有効化）してください。

## 使い方

有効化するだけで動作します。設定項目はありません。

### 動作の仕組み

ミドルウェア（`RoutingMiddleware` の直前に挿入）が、リクエストごとに以下の処理を行います。

1. **旧URL → 新URLへ 301 転送**
   `GET` / `HEAD` で `{ブログURL}archives/xxx` にアクセスされた場合、`{ブログURL}xxx` へ 301 でリダイレクトします。
   `preview` または `force` パラメータ付きのリクエスト（管理画面のプレビュー等）は転送しません。
2. **新URLを内部で読み替え**
   `{ブログURL}xxx` へのアクセスを、内部的に `{ブログURL}archives/xxx` として処理します。ブラウザのURLは変わりません。
3. **出力リンクの書き換え**
   レスポンスが HTML / XML の場合、本文中の `{ブログURL}archives/` を `{ブログURL}` に置換します。

### 対象となるブログURL

`contents` テーブルから `plugin = BcBlog` かつ `type = BlogContent` のレコードの URL を取得します。
URL が `/` のルート直下ブログは対象外です。

### 除外されるパス

- `/baser/` および `/files/` で始まるパス（管理画面・アップロードファイル）
- ブログURL直下の予約語: `archives`, `index`, `tags`, `ajax_add_comment`, `captcha`
- 最初のセグメントに `.` を含むパス（`feed.rss` のような拡張子付きのもの）

## ファイル構成

```
BcRewriteUrl/
├── LICENSE                                 # ライセンス（MIT）
├── README.md                               # このファイル
├── config.php                              # プラグイン情報
├── VERSION.txt                             # バージョン
└── src/
    ├── Plugin.php                          # ミドルウェアの登録
    └── Middleware/
        └── RewriteUrlMiddleware.php        # URL変換・301転送・リンク置換
```

## 注意事項

- ブログURLの一覧は初回アクセス時に取得し、キャッシュします。ブログの追加・URL変更は次のリクエストから反映されます。
- ブログURL直下に予約語と同名のスラッグ（固定ページ等）を置くと、記事URLと衝突する場合があります。
- 出力置換は本文全体への単純な文字列置換のため、本文中に `{ブログURL}archives/` という文字列がそのまま書かれている場合も置換されます。
- ブログコンテンツのURLを変更した場合、変更前のブログURL配下の `/archives/` 付きURLは自動では転送されません。

## ライセンス

本プラグインは MIT License のもとで公開されています。個人・商用問わず、自由に変更・再配布していただいて構いません。

This software is released under the MIT License, see [LICENSE](LICENSE).

## Thanks

- [baserCMS](https://basercms.net/)
- [CakePHP](https://cakephp.org/)
