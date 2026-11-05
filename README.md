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
- 基底権限は `contents: read`、publish jobだけ `packages: write`。通常workflowのログインはそのrunの `GITHUB_TOKEN` のみで、PAT secret・本番Secretsは不要です。checkoutは実在commit SHA固定・認証情報を残しません。buildへDB/AWS/APP_KEY等の秘密を渡しません。

**公開repoのActionsではpackageを自動作成しません。** 既存の両packageがexact private・正しいowner・本repoへの手動関連付けを確認できる場合だけ送信します。`org.opencontainers.image.source` labelによる自動関連付けは行わず、build labelはrevisionのみ維持します。
guardはexact packageのREST metadataについて、name / container型 / owner / `visibility: private` / 関連repoを確認します。publish jobは**両packageの確認→build→login→各packageのpush直前確認→push→直後確認**の順です。未作成・404・public・別repo関連付け・403・不明status・redirect・JSON不正は例外なく停止し、最初の両package確認に失敗した場合はbuild/login/pushを行いません。不一致の診断はfield名だけで、応答の値やtokenを表示しません。visibility変更・削除・PATへの自動fallbackも行いません。

#### 初回準備（Actions外で管理者が手動実施）

以下は準備手順であり、**手動作成法の実API検証は未実施です。修正済みであることは、実packageのprivate化完了を意味しません。** 各確認に失敗したら停止し、本体を送信しないでください。

1. GitHubにowner `shinjo15` としてログインし、プロフィールの **Packages** で `hibilio-backend-app` / `hibilio-backend-nginx` を確認します。**既存Publicの同名packageがある場合はここで停止**してください。同名のprivate packageを新規作成することはできません。既存bootstrapの削除・package削除・改名は別途ユーザー承認が必要で、この手順では実施しません。404だけを未作成の証拠にしないでください。両方とも既存privateを使う場合は、手順2で確認用の一時PATを準備し、手順3の作成を省略して手順4へ進みます。
2. 初回作成を承認した後、ローカルPC（Bash・Docker・GitHub CLI `gh` が必要。本番サーバーやActionsではない）で準備します。[一時PAT classic作成画面](https://github.com/settings/tokens/new?scopes=write:packages)を開き、短い有効期限と `write:packages` のみを選びます（read能力を含みます）。`repo` / `delete:packages` は不要で、チェックされていないことを確認します。PAT値はチャット・Git・本番env・workflow Secretsへ入れないでください。通常workflowは引き続き `GITHUB_TOKEN` のみです。
3. 以下をローカルPCのBashでまとめて実行します。2つとも未作成ならコマンドの置換は不要で、PATは非表示の入力欄にだけ貼り付けます。片方だけ未作成の場合は `for package in ...` の名前一覧を未作成のpackage名だけに変更してください。管理者が未作成と確認できた対象に限ります。ソースlabelもファイルもない `FROM scratch` をstdinから作り、本体を一切含めずに送信します。一時Docker認証領域を使い、終了・エラー・中断時にlogoutと削除を行います（`set -x` や端末録画は使わないでください）。

```
(
  set +x
  set -euo pipefail
  DOCKER_CONFIG=$(mktemp -d)
  export DOCKER_CONFIG
  cleanup() {
    docker logout ghcr.io >/dev/null 2>&1 || true
    unset GHCR_SETUP_PAT
    rm -rf -- "$DOCKER_CONFIG"
  }
  trap cleanup EXIT
  trap 'exit 130' INT
  trap 'exit 143' TERM
  read -r -s -p '一時PAT classicを入力（非表示）: ' GHCR_SETUP_PAT
  printf '\n'
  printf '%s' "$GHCR_SETUP_PAT" | docker login ghcr.io --username shinjo15 --password-stdin
  for package in hibilio-backend-app hibilio-backend-nginx; do
    image="ghcr.io/shinjo15/$package:private-setup"
    printf 'FROM scratch\n' | docker build --network none --tag "$image" -
    docker push "$image"
    confirmed=$(GH_TOKEN="$GHCR_SETUP_PAT" gh api --hostname github.com \
      "/users/shinjo15/packages/container/$package" \
      --jq '.name == "'"$package"'" and .package_type == "container" and .owner.login == "shinjo15" and .visibility == "private" and .repository == null')
    if [ "$confirmed" != true ]; then
      printf '%s\n' 'Private・owner・未接続を確認できないため停止します。' >&2
      exit 1
    fi
  done
)
```

4. GitHubの各packageページで **Private** 表示を確認します。その後、各packageの **Connect repository** で公開repo `shinjo15/HIBILIO_backend` を接続します。公式資料では「公開後に接続したpackageは明示選択しない限りaccessを継承しない」とされていますが、必ず **Package settings** で **Inherit access from repository** がOFFであることと **Private** 表示を確認してください。**Manage Actions access** で本repoを追加し **Write** を付与します。repo接続とActions用のWrite許可はそれぞれ確認します。
5. ローカルPCのrepo rootで次を実行し、接続後も両方のexact private・owner・repoをAPIで確認します。PATの置換は不要、非表示入力のみです。通常workflowのguardそのものを使用します。

```
(
  set +x
  set -euo pipefail
  trap 'unset GH_TOKEN' EXIT
  read -r -s -p '同じ一時PAT classicを入力（非表示）: ' GH_TOKEN
  printf '\n'
  export GH_TOKEN
  python3 -B .github/scripts/check_package_visibility.py --phase pre
)
```

6. 同じローカルPCで認証情報のない一時領域から、両タグの匿名pullが拒否されることを確認します。置換不要です。**両方ともunauthorized / deniedなどの認証・認可拒否を確認**してください。単なるネットワーク障害やtag不在はprivate確認になりません。成功した場合は公開されている可能性があるので停止します。

```
(
  set -eu
  ANON_CONFIG=$(mktemp -d)
  trap 'rm -rf -- "$ANON_CONFIG"' EXIT
  for package in hibilio-backend-app hibilio-backend-nginx; do
    if docker --config "$ANON_CONFIG" pull "ghcr.io/shinjo15/$package:private-setup"; then
      printf '%s\n' '匿名pullが成功したため停止します。' >&2
      exit 1
    fi
  done
)
```

既存privateを再利用した場合は、上の `private-setup` をそのpackageに実在するtagへ置換します。手順3を実行していないpackageにこのtagがあるとは限りません。

7. GitHubの **Settings → Developer settings → Personal access tokens → Tokens (classic)** で一時PATを **Revoke** します。途中で失敗した場合も、作成したPATは必ずrevokeしてください。その後にだけ **Actions → Production images → Run workflow → main** を実行します。GITHUB_TOKENでの読戻しが403等なら設定を見直し、PAT secretを追加して回避しないでください。

失敗したrunでは片方のimage/tagだけが保存される場合があります。**run成功と両方のfull SHA tagを確認するまでは、サーバーのimage指定を更新しないでください。** 同じmain commitの再実行は同じSHAタグを再pushします。管理画面からのvisibility変更との競合をAPI確認だけで原子的に防ぐことはできないため、run中はpackage設定を変更しないでください。

公式[Container registryの認証](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry)は `GITHUB_TOKEN` を推奨し、一時PAT classicで不要なrepo scopeを避けるURLも案内しています。[package権限・visibility](https://docs.github.com/en/packages/learn-github-packages/configuring-a-packages-access-control-and-visibility)は後からrepoを接続した場合の非継承を説明しています。一方、[REST packages](https://docs.github.com/en/rest/packages/packages)の一般説明にはPAT classicが記載されるなど、公式資料の記載差もあります。Publicになる原因を継承と断定せず、実際のAPI/UIで確認してください。HTTP fixtureの検証は、新しい手動準備法や実GHCRでのprivate成功を保証しません。読戻し非対応・権限不足なら本体を送信せず停止します。

ローカルの契約テスト（Python 3とBashが必要・追加Pythonパッケージ／資格情報不要）:

```bash
python3 -B -m unittest discover -s tests/Infrastructure -p 'test_production_images.py' -v
```

### サービスの手動リリース

**main のマージだけでは本番更新しません。** Production images は image 保存までです。
選んだ時刻に backend の **Manual coordinated release** を `main`・未使用 version（例 `v1.0.1`）で実行し、Lightsail backend と既存 Cloudflare Pages `hibilio-web` の frontend を同じサービス版として反映します。
初回の OIDC / production environment / SSH forced command / Pages 自動 production 停止、実行契約、manifest、部分失敗・手動復旧、検証範囲は [docs/release.md](docs/release.md) にまとめています。原子的な切替や migration の自動 rollback は行いません。

再起動・CPU/メモリ上限・ログローテーション・FPM/TLS healthcheckを設定していますが、外部DB/S3/SESの疎通確認は別途必要です。
Nginxは自らTLS終端して `HTTPS=on` をFPMへ渡します。任意のX-Forwarded-Forは信頼しません。
`docker/nginx/cloudflare-realip.conf` に公式 https://www.cloudflare.com/ips-v4 / https://www.cloudflare.com/ips-v6 のCIDRを静的保持し、その送信元だけ `CF-Connecting-IP` を実IPとして復元します。非Cloudflare送信元の偽ヘッダーは無視します。FPMには復元したREMOTE_ADDRを渡すため、Laravelで全proxyをtrustする必要はありません。
デプロイ前に両公式一覧との差分を確認し、更新時はincludeを修正してNginx imageを再ビルドしてください。origin自体のCloudflare以外からの到達制限は別途ホスト側で設定します。

## 本番の初期タグ（明示実行のみ）

`TagSeeder` は承認済みの初期タグ40件だけを追加します。ID・名前は既存 `DemoDataSeeder` のタグと同一で、新規行は `available=true`、先頭15件のみ `pickup=true`（残り25件はfalse）です。デモユーザー・投稿等は作成せず、`DemoDataSeeder` / `DatabaseSeeder` は呼びません。通常の `DatabaseSeeder` にも組み込んでいません。

以下は本番サーバーで管理者が明示実行する手順です（自動実行しません）。必ず稼働中のリリースディレクトリ（対象の `compose.yml` / `compose.production.yml` がある場所）に移動し、接続先・対象imageと、専用DBの既存の自動バックアップの状態・復元方法を確認してください。追加の有料スナップショット作成はこの手順では不要です。既存の本番Compose project `hibilio-production` とDocker認証設定を明示し、クラス指定を省略しないでください。`--no-deps` は依存サービスを起動せず、`--pull never` はimageを取得せず配置済みimageだけを使用します。

```
sudo docker --config /etc/hibilio/docker compose --project-name hibilio-production --env-file /etc/hibilio/compose.env -f compose.yml -f compose.production.yml run --rm --no-deps --pull never -T app php artisan db:seed --class=TagSeeder --force --no-interaction
```

既存IDの行は、名前・有効状態・pickup・作成/更新日時を含め一切変更しません。デモ投入済みの場合も重複しません。追加予定のタグ名が別IDで存在する場合は停止し、その実行で追加したタグをtransaction全体でロールバックします。既存行を削除・改名・統合しません。タグ名にはDBの一意制約がないため、同時のタグ追加・更新やSeeder並列実行は避け、競合は管理者が別途確認してください。

## PostgreSQL回帰テスト（使い捨てDB専用）

開発用Composer依存の導入後、リポジトリrootで以下を実行します。通常の `phpunit.xml` / `task check` はSQLiteのままです。

```bash
docker compose --env-file /dev/null -p hibilio-pg-test -f compose.postgres-test.yml build tests
docker compose --env-file /dev/null -p hibilio-pg-test -f compose.postgres-test.yml run --rm tests
docker compose --env-file /dev/null -p hibilio-pg-test -f compose.postgres-test.yml down --volumes
```

`compose.postgres-test.yml` は外部公開portなしの専用network/volume、固定fixture credentialsを使用します。実DBの接続値に差し替えないでください。
`phpunit.postgres.xml` は固定の `hibilio_postgres_test` とその識別コメント・明示reset許可を確認してからschemaを初期化し、skip/riskyも失敗にします。既存SQLiteファイルは初期化しません。
`TagSeederTest` は通常のSQLite suiteと同じテストをPg18でも実行し、40件の内容、既存行・日時の保持、他テーブルへの書込なし、名前競合時の全体rollbackを確認します。
Pg18で全migrationのrollback/再適用、主キーと不正親拒否・親削除SET NULL、推薦APIの絞込/重複排除/順位、未来・過去の時間境界、非UTC session、statement clockとtransaction clockの差を実行検証します。PHP時刻は固定し、DB時刻は実サーバーを基準にします。clock差の試験だけ専用DB内で2秒待機します。
