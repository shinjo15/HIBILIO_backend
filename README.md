# HIBILIO Backend

Laravel を Docker Compose で動かすバックエンドです。

## 必要環境

- Docker Engine
- Docker Compose v2.30 以降（本番の `env_file.format: raw` を使用）

PHP と Composer をホストへインストールする必要はありません。

## 起動

```bash
docker compose up --build --detach
```

アプリケーションは http://localhost:8001 で確認できます。

停止する場合:

```bash
docker compose down
```

## Laravel コマンド

すべて `app` コンテナ内で実行します。

```bash
# テスト
docker compose exec --no-TTY app php artisan test

# マイグレーション
docker compose exec --no-TTY app php artisan migrate

# Artisan コマンド一覧
docker compose exec --no-TTY app php artisan list
```

開発は SQLite、Redis、Mailpit（http://localhost:8026）を使用します。
初回は `docker compose run --rm --no-deps app composer install` を実行し、
`.env.example` を `.env` にコピーして `php artisan key:generate` をapp内で実行してください。
`task check` と既存CIは引き続き開発構成を使用します。
ポートが競合する場合は `APP_BIND_ADDRESS=127.0.0.1 APP_PORT=18081 MAILPIT_BIND_ADDRESS=127.0.0.1 MAILPIT_PORT=18026` 等を指定できます。

## 共通構成と環境差分

- `compose.yml`: appのRedis接続、Redisとそのhealthcheck。
- `compose.override.yml`: 通常の `docker compose` が自動merge。development target、artisan serve、bind mount、Mailpit、開発ポート。
- `compose.production.yml`: 必ず `-f compose.yml -f compose.production.yml` で明示mergeし、開発overrideは含めません。ビルド済みapp/FPMとNginx/TLS、外部PostgreSQLのCA検証、S3、SES、外部非公開Redisを使用します。

Dockerfileは共通PHP拡張からdevelopment / productionへ分岐し、productionの依存は `composer install --no-dev` で固定します。
同じsource stageのpublicをNginxにも格納します。本番はソースのbind mountなし・read-only rootで、書込先は一時storage/cacheのみです。
セッション・キャッシュは外部DB、画像はS3に置く前提です。Redisのパスコードは専用volumeへAOF永続化します。
現状は非同期Job・定期処理がなく、queueはsyncのためworker/schedulerは起動しません。追加時はappと同一commit imageを使い、必要なサービスだけ追加してください。

## 本番の準備・ビルド/デプロイ契約

PostgreSQL 18の専用fixtureで全migrationのfresh・全rollback・再適用と主キー/自己参照FKの制約、`GET /api/posts/favorite_tags` の順位・絞込・paginationを検証しています。初期migrationの自己参照FKより前にprimary keyを登録し、参照先の一意制約不足（SQLSTATE 42830）を解消しました。これは未本番適用の初期migration修正で、既存DBに自動再適用はしません。
推薦スコアは既存の加点0.5と整数時間の0方向切捨てを維持します。PgではUTC/秒精度の `statement_timestamp()` と `TRUNC` を使い、PHPのテスト時刻・transaction開始時刻・DB session timezoneに依存させません。本番DB全体や実データでの検証完了を意味するものではありません。
画像は既存の `ACCOUNT_IMAGE_STORAGE=s3` で保存先とURL取得先をS3へ切替可能です（production構成で設定済み）。
S3画像uploadは既存Storage/driverを維持し、呼出時の `before_upload` でSDK commandのACLを除去します。固定fixtureのHTTP transportで、serializer通過後のPUTに `x-amz-acl` ヘッダーがないことを検証しています。現行VO上限（icon 5MiB / header 10MiB）はSDK既定multipart閾値16MiB未満です。上限・閾値を変更する場合はmultipart開始要求を含めて別対応が必要です。非公開性はBucketOwnerEnforcedとIAM/bucket policyで維持します。
画像URLは `fileExists` でHEADだけを行い、200なら従来の1時間署名URL、404ならnullを返します。ディレクトリ判定用ListObjectsへのfallbackは行わず、403等の失敗は握りつぶしません。local driverのnull/署名契約も維持します。
未解決: 現在のGet/Put/DeleteのみのIAMでは、画像不存在のHEADがListBucket不足により403となり、nullを返せません。既存null契約を安全に維持するには、ListBucketを含む必要権限の別承認・実AWS検証が必要です。今回IAM・公開範囲・API契約は変更していません。S3テストはダミー資格情報と `.invalid` endpointのHTTP fixtureであり、実AWS/BucketOwnerEnforced環境の動作保証ではありません。

サーバー上で、リポジトリ外に次を用意します（exampleは `docker/*.env.example`、値は置換必須）。

- `/etc/hibilio/production.env`: APP_KEY、公開URL、DB接続、bucket、SES送信元等。root所有・0600。
- `/etc/hibilio/aws.env`: AWS credentials/region。root所有・0600。appへenv_fileで注入し、imageへは含めません。
- `/etc/hibilio/redis.env`: 空でない専用 `REDIS_PASSWORD` のみ。root所有・0600。appとRedisに共有し、RedisへAWS/DB等の秘密は渡しません。ログイン用パスコードとは無関係です。
- `/etc/hibilio/postgres-ca.crt`: 外部PostgreSQLの信頼CA。コンテナのwww-dataが読める権限（CAは公開情報）。DB_HOSTは証明書のホスト名と一致させます。DB_URLによるTLS設定の上書きはしないでください。
- `/etc/hibilio/tls/origin.crt` / `origin.key`: NginxのTLS証明書/秘密鍵。秘密鍵はroot所有・0600、親directoryはroot専用。Compose secretsでread-only mountします。
- `/etc/hibilio/compose.env`: `docker/compose.production.env.example` を参考にimage名/外部ファイルのパスだけを設定。秘密値は置きません。

### GitHub Actionsで非公開GHCRへ保存

`.github/workflows/production-images.yml`（Actionsの **Production images**）が、同じcheckoutから `production` / `nginx` targetを `linux/amd64` でビルドします。
保存先は `ghcr.io/shinjo15/hibilio-backend-app:<full-github.sha>` と `ghcr.io/shinjo15/hibilio-backend-nginx:<full-github.sha>` です。両方を同一commitで指定し、`latest` は作成しません。

- `main` 宛のPRはビルドと公開契約テストのみ。registryへログインせず、`packages: write` も付与しません。既存CIの品質ゲートは複製しません。
- publishは `shinjo15/HIBILIO_backend` の `main` push、または同repoのActions → **Production images** → **Run workflow** でbranch **main** を指定した場合のみです。fork・別repo・別ref・PRからはpublishしません。`pull_request_target` は使用しません。
- 標準GitHub-hosted `ubuntu-24.04`、job timeoutは30分。同じPRは旧runをキャンセルし、mainのpublishは同時実行しません。mainでは検証jobとpublish jobによる二重ビルドを避けます。
- 基底権限は `contents: read`、publish jobだけ `packages: write`。ログインはそのrunの `GITHUB_TOKEN` のみで、新規PAT・本番Secretsは不要です。checkoutは実在commit SHA固定・認証情報を残しません。buildへDB/AWS/APP_KEY等の秘密を渡しません。

公開repo由来でもGHCR packageのvisibilityは別管理で、初回はprivateが既定です。各imageに `org.opencontainers.image.source=https://github.com/shinjo15/HIBILIO_backend` を設定し、repo関連付けとworkflowのpackage accessを有効にします。
guardはexact packageのREST metadataについて、name / container型 / owner / `visibility: private` / 関連repoを確認します。両packageの確認完了後にだけ本体をpushし、各push直前・直後も読戻します。public・別repo関連付け・権限不足・不明status・redirect・JSON不正は停止します。visibility変更や削除は一切行いません。

初回だけの安全策: preの404（JSON objectのmessageが `Not Found` または `Package not found.`）は未作成と断定せず、**空bootstrapだけを許可する終了コード**として扱います。同packageへ `FROM scratch` と公開source labelだけのimageを `bootstrap-<run-id>-<attempt>` タグでpushし、その後のmetadataがexact private・本repo関連付けの200になることを必須にします。Dockerfileはstdinから渡しbuild contextは空で、アプリ・ソース・本番秘密を含みません。404でpublic既存packageが隠れていた場合も、本体を送る前に停止しますが、空markerがそのpackageに残る可能性があります。markerは自動削除せず、デプロイには使いません。postの404は許可しません。既存publicをprivateへ変更したり、同名の別packageを勝手に移行したりしないでください。

初回実行前にrepoのActionsが有効で、package作成を禁止する設定がないことを確認してください。既存private packageを使う場合は、そのPackage settingsで関連repoと **Manage Actions access** の本repoへのwrite権限を確認します。403や読戻し失敗はPATへ自動fallbackせず停止するので、まず設定を確認してください。
失敗したrunでは片方のimage/tagだけが保存される場合があります。**run成功と両方のfull SHA tagを確認するまでは、サーバーのimage指定を更新しないでください。** 同じmain commitの再実行は同じSHAタグを再pushします。管理画面からのvisibility変更との競合をAPI確認だけで原子的に防ぐことはできないため、run中はpackage設定を変更しないでください。

公式[Container registryの認証](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry#authenticating-in-a-github-actions-workflow)はgranular repo packageへの `GITHUB_TOKEN` を推奨し、[package権限](https://docs.github.com/en/packages/learn-github-packages/configuring-a-packages-access-control-and-visibility)はmetadata readを説明しています。一方、[REST packages](https://docs.github.com/en/rest/packages/packages)の一般説明にはPAT classicが記載されています。**このrepoのGITHUB_TOKENによるexact metadata読戻し・初回bootstrap・実GHCR publishは未実行で、実packageの存在/visibilityも未確認です。** HTTP fixtureで契約を検証していますが、実権限での成功保証ではありません。読戻し非対応・権限不足なら本体を公開せず失敗します。

ローカルの契約テスト（Python 3とBashが必要・追加Pythonパッケージ／資格情報不要）:

```bash
python3 -B -m unittest discover -s tests/Infrastructure -p 'test_production_images.py' -v
```

### サーバーへの反映（別途手動実施）

このworkflowはimage保存までで、Lightsail更新・SSH・DB操作・DNS・自動デプロイは行いません。以下は既存の手動デプロイ契約です。

サーバーでは `/etc/hibilio/compose.env` のAPP_IMAGE / NGINX_IMAGEを同一commit tagへ更新し、root権限で実行します。
`config --quiet` は秘密値を表示せず、外部envファイルやmergeを確認できます。

```bash
docker compose --env-file /etc/hibilio/compose.env -f compose.yml -f compose.production.yml config --quiet
docker compose --env-file /etc/hibilio/compose.env -f compose.yml -f compose.production.yml pull
docker compose --env-file /etc/hibilio/compose.env -f compose.yml -f compose.production.yml up --detach --no-build --wait
```

DBマイグレーションは起動時に実行しません。バックアップ・互換性を確認したうえで、同じCompose引数を使い `run --rm app php artisan migrate --force` を明示実行します。
再起動・CPU/メモリ上限・ログローテーション・FPM/TLS healthcheckを設定していますが、外部DB/S3/SESの疎通確認は別途必要です。
Nginxは自らTLS終端して `HTTPS=on` をFPMへ渡します。任意のX-Forwarded-Forは信頼しません。
`docker/nginx/cloudflare-realip.conf` に公式 https://www.cloudflare.com/ips-v4 / https://www.cloudflare.com/ips-v6 のCIDRを静的保持し、その送信元だけ `CF-Connecting-IP` を実IPとして復元します。非Cloudflare送信元の偽ヘッダーは無視します。FPMには復元したREMOTE_ADDRを渡すため、Laravelで全proxyをtrustする必要はありません。
デプロイ前に両公式一覧との差分を確認し、更新時はincludeを修正してNginx imageを再ビルドしてください。origin自体のCloudflare以外からの到達制限は別途ホスト側で設定します。

## PostgreSQL回帰テスト（使い捨てDB専用）

開発用Composer依存の導入後、リポジトリrootで以下を実行します。通常の `phpunit.xml` / `task check` はSQLiteのままです。

```bash
docker compose --env-file /dev/null -p hibilio-pg-test -f compose.postgres-test.yml build tests
docker compose --env-file /dev/null -p hibilio-pg-test -f compose.postgres-test.yml run --rm tests
docker compose --env-file /dev/null -p hibilio-pg-test -f compose.postgres-test.yml down --volumes
```

`compose.postgres-test.yml` は外部公開portなしの専用network/volume、固定fixture credentialsを使用します。実DBの接続値に差し替えないでください。
`phpunit.postgres.xml` は固定の `hibilio_postgres_test` とその識別コメント・明示reset許可を確認してからschemaを初期化し、skip/riskyも失敗にします。既存SQLiteファイルは初期化しません。
Pg18で全migrationのrollback/再適用、主キーと不正親拒否・親削除SET NULL、推薦APIの絞込/重複排除/順位、未来・過去の時間境界、非UTC session、statement clockとtransaction clockの差を実行検証します。PHP時刻は固定し、DB時刻は実サーバーを基準にします。clock差の試験だけ専用DB内で2秒待機します。
