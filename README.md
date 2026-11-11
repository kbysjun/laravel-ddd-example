# Laravel DDD REST API Example

DDDの考え方を取り入れたREST APIの設計・実装を示すサンプルプロジェクトです。投稿の作成・取得・削除を題材に、ドメインの表現、レイヤー間の依存関係、認証・認可、テストとCIを一貫した構成で実装しています。

## このサンプルで示すこと

| 観点 | 実装・設計のポイント |
|---|---|
| 責務分担 | Controller・UseCase・Domain・Repository・Presenterの役割を分離 |
| ドメインの表現 | EntityとValueObjectで状態と本文の不変条件を表現 |
| 永続化の抽象化 | Repositoryの契約をDomainに置き、SQLをInfrastructureに集約 |
| セキュリティ | Bearer認証、所有者による削除認可、入力検証、公開項目の制限 |
| 可読性 | 意図が分かる操作名と小さなクラス、明示的な入出力 |
| 品質管理 | Unit・Feature・依存関係のテスト、コードスタイル確認、CI |

レビュー時は [本文のValueObject](app/Domain/ValueObjects/PostBody.php)、[Repositoryの契約](app/Domain/Repositories/PostRepositoryInterface.php)、[削除UseCase](app/UseCases/Post/DeletePost.php)、[Repository実装](app/Infrastructure/Repositories/DatabasePostRepository.php)、[APIテスト](tests/Feature/PostApiTest.php) を中心に確認できます。

## 実装範囲と検証状況

Laravel **13系** / PHP **8.5以上**を対象とします。投稿APIとローカル開発環境、テスト、CI設定を実装しています。ログイン・ユーザー登録・画面UIは実装範囲に含めず、動作確認用のトークン発行コマンドを用意しています。

| 確認項目 | 結果 |
|---|---|
| コンテナ内の依存インストール | 成功。composer.lock生成済み（Laravel 13.42.0） |
| このプロジェクト自身のvendorでのテスト | PHP 8.5.12でSQLite・MySQLとも14テスト・110アサーション成功 |
| composer check | Composer設定検証・Pint（43ファイル）・SQLiteテスト成功 |
| composer audit | 脆弱性の報告なし |
| DockerでのHTTP動作 | Nginx→PHP-FPM→MySQLで起動・認証・入力検証・投稿の作成/取得/削除を確認 |
| 初回セットアップの再現 | vendor・.env・実行時データを除いたコピーと空のDBボリュームで、依存取得・キー生成・マイグレーション・両DBテスト・HTTP操作まで成功 |
| GitHub Actions | [初回CI成功](https://github.com/kbysjun/laravel-ddd-example/actions/runs/43062487512)。SQLite/MySQLテスト・Pint・Composer検証・監査を確認 |

2026年11月12日に別Composeプロジェクトで初回手順を確認しました。Dockerのイメージ・ビルドキャッシュは共有しており、完全なオフライン実行やキャッシュなしの全イメージ再ビルドは検証対象外です。初回はDockerイメージとComposer依存の取得にインターネット接続が必要です。

`composer.lock` に依存バージョンを固定しています。取得したソースをGit管理する際は、lockファイルも含めてコミットしてください。

## 技術構成

| 用途 | 採用 |
|---|---|
| API | Laravel 13 / PHP 8.5 |
| 認証 | Laravel Sanctum / Bearer token |
| DB | MySQL 8.4 LTS |
| Web | Nginx → PHP-FPM |
| テスト | PHPUnit 12 / SQLite + MySQL |
| スタイル | Laravel Pint |
| CI | GitHub Actions / PHP 8.5 |

Docker環境はNginx・PHP-FPM・MySQLの3サービスで構成します。サンプルの実行と設計の確認に必要な構成に絞っています。固定container_name・固定プロジェクト名・共有ネットワークを使いません。DBとPHP-FPMはホストへ公開しません。HTTPは127.0.0.1のみで公開し、既定の `HTTP_PORT=0` ではDockerが空きポートを自動割り当てします。

### ポート・起動ディレクトリの指定

`.env` の設定で固定ポートにも変更できます。指定ポートが使用中なら起動エラーになります。競合を自動回避したい場合は0を使ってください。

| 設定 | 既定 | 用途 |
|---|---|---|
| HTTP_PORT | 0 | 自動割り当て。例: 18080を指定すると固定 |
| HTTP_BIND_ADDRESS | 127.0.0.1 | ローカルだけに公開 |
| COMPOSE_PROJECT_NAME | ディレクトリ名 | 同名ディレクトリのコピーは別名を指定 |

通常は、このリポジトリのルート（`compose.yaml` があるディレクトリ）で `docker compose` を実行します。

```bash
cd /path/to/laravel-ddd-example
docker compose up -d --build
docker compose port nginx 80
docker compose down
```

別のディレクトリから操作する場合は、標準の `--project-directory` と `-f` オプションで配置先を指定できます。以下のパスは実際の配置先に置き換えてください。

```bash
docker compose --project-directory /path/to/laravel-ddd-example \
  -f /path/to/laravel-ddd-example/compose.yaml up -d --build
```

一時的な指定もできます。同じプロジェクト名で起動すると同じ環境の操作になるため、並行起動時は名前も分けます。ネットワーク・DBボリュームもプロジェクト名ごとに分離されます。

```bash
HTTP_PORT=18081 COMPOSE_PROJECT_NAME=ddd-example-review docker compose up -d
HTTP_PORT=18081 COMPOSE_PROJECT_NAME=ddd-example-review docker compose down
```

自動割り当てのポートはコンテナ再作成で変わる場合があります。固定URLが必要なら固定ポートを設定してください。

## クイックスタート

Docker Composeが利用できる環境で、リポジトリのルートから実行してください。開発時のPHP・Composerはappコンテナ内で実行し、ホスト側へのインストールは不要です。まずコンテナを起動し、その中で依存インストール・キー生成・マイグレーションを行います。

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app bash
```

コンテナ内の作業ディレクトリは `/app` です。以下をコンテナ内で実行します。依存インストールが終わるまではAPIへアクセスできません。

```bash
composer install --no-interaction --prefer-dist
php artisan key:generate
php artisan migrate
composer check
php artisan demo:token
exit
```

初回セットアップ後の起動と停止は、次のコマンドで行います。

```bash
docker compose up -d
docker compose down
```

固定ポートを使う場合は `.env` の `APP_URL` も接続先に合わせてください。自動割り当て時のLocationヘッダはリクエスト先のホスト・ポートから生成されます。

`demo:token` はlocal環境のみで利用でき、デモユーザーと有効期限1時間のトークンを発行します。表示したトークンをGitに保存しないでください。通常のAPIはComposeから渡す設定でMySQLを使い、テストはコンテナ内でもSQLiteのメモリDBを使います。

```bash
# 自動割り当てされた接続先（例: 127.0.0.1:49153）を取得
export API_URL="http://$(docker compose port nginx 80)"
export TOKEN='上で発行したトークン'
curl -i ${API_URL}/up
curl -i ${API_URL}/api/v1/posts \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"body":"DDDの投稿サンプル"}'
curl -i ${API_URL}/api/v1/posts/取得したUUID \
  -H "Authorization: Bearer $TOKEN"
curl -i -X DELETE ${API_URL}/api/v1/posts/取得したUUID \
  -H "Authorization: Bearer $TOKEN"
```

停止は `docker compose down`。`docker compose down -v` はDBデータも削除するため初期化が必要な場合のみ実行します。

## API仕様

全投稿APIは認証必須。投稿本文は認証済み全ユーザーが閲覧でき、削除は投稿者だけが実行できます。

| Method | URI | 成功 | 主な失敗 |
|---|---|---|---|
| POST | /api/v1/posts | 201 + Location | 401 / 422 / 429 |
| GET | /api/v1/posts/{uuid} | 200 | 401 / 404 / 429 |
| DELETE | /api/v1/posts/{uuid} | 204 | 401 / 404 / 429 |
| GET | /up | 200 | Laravelの起動確認のみ |

作成リクエスト: `{"body":"投稿本文"}`。前後空白除去後1〜500文字。`author_id` と `id` の指定は拒否します。それ以外の未知フィールドは保存対象に含めません。

成功レスポンス: `{"data":{"id":"UUID","body":"投稿本文"}}`。エラーはLaravel標準のJSON形式（`message`、入力エラー時は `errors`）を使います。存在しない投稿と他人の投稿の削除はどちらも404に統一します。

## DB設計・制約・インデックス

業務データは `users`（投稿者）と `posts`（投稿）です。1ユーザーが複数の投稿を持ち、各投稿は必ず1ユーザーに属します。認証トークンはSanctumの `personal_access_tokens` に保存します。定義の正本は `database/migrations` です。

| テーブル | 主な列と制約 | インデックス・目的 |
|---|---|---|
| users | id: 自動採番の主キー、name/email/password: NOT NULL、email_verified_at/remember_token: nullable | emailに一意制約。メールアドレスの重複を防止 |
| posts | id: UUID主キー、author_id: NOT NULLの外部キー、body: NOT NULLのTEXT、created_at/updated_at | idの主キーで取得・削除。MySQLではauthor_idの外部キー用インデックスが作成される |
| personal_access_tokens | id: 自動採番の主キー、tokenable_type/tokenable_id: 所有者、token: 64文字のハッシュ、abilities/last_used_at/expires_at: nullable | tokenに一意制約、所有者の複合インデックス、有効期限のインデックス |

`posts.author_id` は `users.id` を参照し、ユーザー削除時は投稿も削除します（ON DELETE CASCADE）。投稿IDと投稿者IDの組み合わせで所有者による削除を判定しますが、idが一意なので追加の複合インデックスは設けていません。投稿一覧・並べ替え用のインデックスは、該当APIが未実装のため追加していません。

本文の前後空白除去・1〜500文字という制約はFormRequestとPostBodyで保証します。DBには文字数や空文字のCHECK制約を設けていないため、直接SQLで書き込む経路では同じ保証はありません。UUIDの書式もアプリ側で保証します。

トークン所有者はSanctumのポリモーフィック関連で、DBの外部キーはありません。ユーザー削除時にトークンを削除する機能・期限切れトークンの定期削除は、このサンプルでは用意していません。ユーザー削除APIも実装範囲外です。

Laravel標準の補助テーブルもマイグレーションに含まれます。

| 用途 | テーブルと主な制約・インデックス |
|---|---|
| パスワード再設定 | password_reset_tokens: emailが主キー。再設定APIは未実装 |
| DBセッション | sessions: idが主キー、user_id・last_activityにインデックス。user_idの外部キーなし |
| DBキャッシュ | cache/cache_locks: keyが主キー |
| DBキュー | jobs: idが主キー、queueにインデックス。job_batches: idが主キー。failed_jobs: idが主キー、uuidに一意制約 |

既定はファイルセッション・ファイルキャッシュ・同期キューです。補助テーブルが存在することは、それらの機能がAPIとして実装済みであることを意味しません。SQLiteテストでも外部キーを有効にしていますが、型・照合順序・索引の動作はMySQLと異なるため、MySQLテストも実行します。

## DDDの適用方針

DDD（Domain-Driven Design／ドメイン駆動設計）は、扱う業務の概念やルールをモデルとして表し、その言葉とルールを実装に反映する設計手法です。ディレクトリをレイヤーに分けるだけでDDDが成立するわけではありません。本サンプルでは「投稿」という小さな領域に対象を絞り、Entity・ValueObject・Repositoryの使い分けと、業務ルールを技術的な処理から分離する方法を示します。

### モデルと言葉の対応

| 概念 | このサンプルの表現 | 判断の理由 |
|---|---|---|
| 投稿 | `Post` Entity | 本文が同じでもIDが異なれば別の投稿として識別する |
| 投稿本文 | `PostBody` ValueObject | IDを持たず、前後空白を除去した1〜500文字という条件を生成時に保証する |
| 投稿者 | `Post.authorId` | 認証済みの操作主体と投稿の所有者を区別する |
| 投稿の永続化 | `PostRepositoryInterface` | 保存方式を利用側から隠し、ドメインの型で入出力を定義する |
| 作成・取得・削除 | 各UseCase | 操作ごとに必要なモデルとRepositoryを組み合わせる |

### 業務ルールとアプリケーション処理の境界

本文の文字数・空文字禁止は、HTTP以外の経路から作成しても守る必要があるため、`PostBody`で保証します。FormRequestはその手前で入力形式を検証し、HTTPクライアントへ422を返す役割を担います。

認証はHTTP境界で行い、UseCaseへ認証済みのユーザーIDを渡します。「投稿者だけが削除できる」という規則は、削除UseCaseがRepositoryの所有者条件付き削除を呼ぶことで適用します。実装側は所有者条件と削除を単一SQLにまとめ、確認と更新の間に状態が変わる問題を避けます。

DomainはHTTP・DB・Laravelに依存しません。Repositoryの実装がDomainの契約に従う依存関係にすることで、保存方式の変更がドメインの型やルールへ波及する範囲を抑えます。

### 集約とサンプルの範囲

投稿を操作単位とする小さなモデルで、関連する子Entityや複数オブジェクト間の不変条件はありません。そのため、複雑な集約、Domain Service、Domain Event、独立したFactoryは導入していません。ユーザーの認証情報はLaravelの認証モデルで扱い、投稿Entityへ取り込みません。

現状はDDDの戦術的なパターンを中心としたサンプルです。実際の業務分析に基づく境界づけられたコンテキストの分割やコンテキストマップは扱いません。要件が増えた際は、モデル間で守るべきルールと更新の整合性を基準に、集約の境界や必要な抽象化を見直します。

## アーキテクチャと責務

依存方向: **Http → UseCases → Domain ← Infrastructure**。DIによってRepository実装を差し替えます。パッケージの自動検出は無効化し、SanctumのProviderはbootstrap/providers.phpに明示登録しています。開発依存のCollisionは、インストールされている場合のみAppServiceProviderからCLI向けに登録し、artisan testを有効にしています。新規パッケージ追加時も必要なProviderを確認して登録します。

| 配置 | 責務 | 持ち込まないもの |
|---|---|---|
| Domain/Entities | 投稿の状態 | HTTP、Eloquent、認証Facade |
| Domain/ValueObjects | 本文の不変条件 | DB、リクエスト |
| Domain/Repositories | 保存・取得・所有者条件付き削除の契約 | Builder、Model、HTTPレスポンス |
| UseCases/Post | 操作の組み立て、認証主体を利用した認可 | Request、auth()、具象Repository |
| Infrastructure/Repositories | SQL、保存形式からEntityへの復元 | HTTPステータス、レスポンス整形 |
| Http/Requests | 入力の型・長さ・禁止項目の検証 | DB更新 |
| Http/Controllers | 認証済み主体と入力をUseCaseへ渡す | SQL、業務判断 |
| Http/Presenters | 公開レスポンスの許可リスト | Repository呼び出し |
| Providers | DI登録、レート制限 | 投稿ロジック |

処理は `Route → Sanctum → FormRequest → Controller → UseCase → Repository → Presenter`。Domainは純粋なPHPで、UseCaseは必要最小限のLaravelユーティリティ（UUID生成）を使います。完全なフレームワーク独立が必要ならUUID生成もContractに切り出します。

### 設計上の選択と理由

- **操作ごとのUseCase**: 作成・取得・削除を個別のクラスに分け、依存と処理の流れを追いやすくします。
- **用途に沿ったRepository契約**: 汎用的なFluent Queryではなく、操作名から意図を読み取れるメソッドを公開します。
- **Entityを返す永続化境界**: RepositoryはEntityまたは値を返し、Eloquent ModelやBuilderを外へ返しません。
- **Query Builderによる実装**: このサンプルの保存処理は単純なため、投稿用のEloquent Modelを追加せず、SQLへの変換をRepository内で完結させます。
- **投稿IDにはUUID**: 公開用IDにUUIDを採用します。IDを知っていることと操作権限を持つことは分け、削除時は所有者条件を必ず確認します。
- **必要な抽象化に限定**: 単一SQLの操作にTransaction管理や汎用基底クラスを追加せず、現在の要件に対して責務が明確になる構成を選びます。

### Repositoryとトランザクション

`save` は新規作成専用です。曖昧なupsertや汎用CRUD基底クラスを導入せず、用途に沿った小さな契約を定義します。`find` はnullable Entityを返し、UseCaseが未存在をアプリケーション例外に変換、HTTP境界で404に変換します。

削除は `deleteOwned(id, actorId)` の単一SQLで所有者を確認して実行します。取得後に削除する二段階の認可を避け、結果件数で成否を判定します。現状の書き込みは単一SQLなので明示トランザクションは不要です。複数の集約更新やイベント追加が必要になったらUseCase単位のTransaction ContractとOutboxを検討します。

本文の検証をHTTPとValueObjectに置くのは、HTTP入力エラーを422で返しつつ、CLI・ジョブからの不正なドメイン生成も防ぐためです。Entityはreadonly、永続化の項目は明示的に列挙します。

## セキュリティ設計

- 認証ユーザーIDをサーバーから渡し、リクエスト由来の所有者IDは信頼しません。
- Bearer tokenのみを受け付け、有効期限を設定。認証と所有者認可を別の責務にします。
- UUIDを認可の代用にしません。所有者違いによる削除をFeatureテストで検証します。
- 1ユーザーあたり60回/分。単一インスタンス向けです。水平分散時は共有キャッシュを導入します。
- JSONの公開項目をPresenterで制限。DBの列追加で意図しない項目が公開されません。
- `.env` と秘密情報はGit管理外。共有するのは `.env.example` のみです。
- Docker構成はローカル開発専用です。本番用のTLS・非root実行・権限・秘密管理は別途必要です。

詳細と報告方針は [SECURITY.md](SECURITY.md) を参照してください。

## テストとCI

初回セットアップで依存関係をインストールした後、起動済みのappコンテナに入って実行します。

```bash
# ホスト側
docker compose exec app bash
```

```bash
# コンテナ内
composer check
composer audit
exit
```

`composer check` はComposer設定の検証、Pint、テストを実行します。テストは `phpunit.xml` によりSQLiteのメモリDBを使用するため、テスト用の事前マイグレーションは不要です。Composerの依存取得が失敗した場合は、コンテナからの外部通信を確認し、インストールを完了させてからテストしてください。

シェルに入らず、同じ処理をコンテナ内で直接実行することもできます。

```bash
docker compose exec app composer check
docker compose exec app composer audit
docker compose exec app php artisan test
```

MySQLでのテストは、appコンテナ内で `composer test:mysql` を実行します。開発用とは別の `testing` データベースを使用します。ComposeのDBを初回作成するときに、初期化SQLがtestingデータベースと接続権限を作成します。

既存のDBボリュームでは初期化SQLが自動実行されないため、ホスト側で次を一度実行してください。既存データを削除せずにtestingデータベースと権限を追加します。

```bash
docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot' < .docker/mysql/init-testing.sql
docker compose exec app composer test:mysql
```

**注意:** `RefreshDatabase` は指定DBを再構築します。通常はphpunit.xmlでSQLiteのメモリDBを強制し、開発DBへの誤接続を防ぎます。MySQL専用設定はtestingデータベースを強制します。両設定でDB_URLを無効化し、URLによる接続先の上書きを防ぎます。回帰テストでは外部DB_URLを設定した子プロセスで両設定を読み込み、DBに接続せず接続先を検証します。本番接続情報でテストしないでください。

```bash
docker compose exec app composer validate --strict
docker compose exec app vendor/bin/pint --test
docker compose exec app php artisan test
```

| 分類 | 検証内容 |
|---|---|
| Unit | 本文の空・長さ・日本語境界、レイヤー依存の禁止 |
| Feature | 作成→取得→削除、未認証、他人の削除、投稿者偽装、入力エラー |
| Security | 実Bearer token、有効期限切れ、不正token、レート制限 |
| CI | PHP 8.5、SQLite/MySQL、Pint、Composer設定、依存脆弱性 |

`composer install` はコミットした `composer.lock` に従って依存関係をインストールします。依存更新時は **composer.lockも必ずコミット**し、CI・開発で同じ依存バージョンを再現してください。Pintによる整形はコンテナ内で `vendor/bin/pint` を実行します。DependabotはComposer・Actions・PHP Dockerfileの更新PRを作成します。

## 今後の拡張候補

更新・ページング、認証発行/失効API、OpenAPI仕様、楽観ロック、UseCase単体テスト、静的解析を順次追加できます。現時点ではそれらを実装済みとはしていません。

## ライセンス

LaravelスケルトンはMITライセンスです。本サンプルもMITとして公開します。[LICENSE](LICENSE) を参照してください。

### 配布対象と依存ライセンス

公開するのはソース、設定例、composer.lock、LICENSEです。`.env`、auth.json、vendor、ログ、実行時キャッシュ、ローカルDBファイルは含めません。フォルダ全体のZIP化では.gitignoreが自動適用されないため、除外済みソースから配布してください。Composeのパスワードはローカル専用の例示値で、実サービスの認証情報ではありません。

現在のlockには開発依存を含め106パッケージがあります。ComposerのライセンスメタデータはMITが74件、BSD-3-Clauseが29件、Apache-2.0が1件、BSD/GPLの選択式が2件（nette/schema・nette/utils）です。Netteの同梱license.mdにはBSDを選択できることが明記されています。依存の詳細は `composer licenses` で確認でき、著作権・ライセンス本文は各依存に同梱されています。プロジェクトのMIT表記は各依存のライセンスを置き換えません。

ローカルで確認したソースには顧客データや実サービスの秘密情報は見つかっていません。ただし、初回コミット以前の履歴や提供者によるコードの出所は未確認です。提供者によるコードの出所と公開権限の確認は別途必要です。脆弱性の報告にはSECURITY.mdにある非公開窓口を使用してください。
