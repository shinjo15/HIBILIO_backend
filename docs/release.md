# サービスの手動 coordinated release

## 契約と境界

main のマージはデプロイではありません。既存の **Production images** は今までどおり main の app/nginx image を非公開 GHCR へ保存するだけです。
本 backend repo の **Manual coordinated release** を branch `main`、version `v1.0.1` 等で明示実行した時点で、両 repo の main を full SHA に固定して、次を一つのサービス版として反映します。

- Backend: `shinjo15/HIBILIO_backend` → Lightsail `hibilio-production-api` / `ap-northeast-1`。
- Frontend: 公開 repo `shinjo15/HIBILIO_web_frontend` → 既存 Pages project `hibilio-web` の production (`main`)。別 project は作りません。
- backend の Git tag / GitHub Release が両 SHA、app/nginx digest、frontend dist digest、Compose SHA-256、以前の backend 状態・Pages deployment ID を記録します。frontend repo への tag/write PAT は不要です。

**原子的な切替ではありません。** backend → frontend の順で、一時的に新旧が混在します。前後方互換な API / migration を用意し、既存 DB バックアップの状態と復旧方法を確認してから実行してください。新しい有料 AWS サービスや snapshot は作成しません。migration は取り消せないため、自動 rollback はありません。

version は `vMAJOR.MINOR.PATCH` のみ（各数値は 0 または先頭ゼロなし最大6桁）。suffix、空白、任意 ref/URL/path/command は受け取りません。予約済み tag、draft、失敗版も再利用しません。明示 commit 入力は設けず、選んだ時刻の両 main だけを使用します。

## 初回設定：管理者がユーザーと別途実施

以下は初回設定・確認の手順です。既存の environment / Release / クラウド設定を確認し、不足する設定だけを管理者が実施してください。既に設定済みの環境を作り直さないでください。実 CD の確認済み範囲と公開時の障害は後述の「v1.0.0 の公開メタデータ障害」を参照してください。値や秘密をチャットや Git に貼らないでください。設定中も既存 SSH / browser SSH / Cloudflare 443 を維持します。

### 1. GitHub production environment / immutability

backend repo → **Settings → Environments** で `production` を確認し、存在しない場合だけ **New environment** から作成します。
**Deployment branches and tags → Selected branches and tags** で **Branch: main のみ**を許可します。全 branch、同名 tag、pattern `*` は許可しません。必要な reviewer、自己承認禁止、管理者 bypass 制限も設定してください。
workflow の repo/ref/event guard と environment の main 制限の両方が必要です。environment の OIDC subject には branch が含まれないため、IAM trust だけでは main を強制できません。

backend repo → **Settings → General → Releases → Enable release immutability** を有効にします（今後公開する版に適用）。公開済み版の asset / Git tag を固定します。workflow は既存版を上書きせず、最終 manifest を `release.json` asset として digest 読戻し後に公開し、公開後 `immutable=true` も確認します。
設定読取 API は admin read が必要で、通常の GITHUB_TOKEN へ administration 権限は追加しません。この初回管理者確認は必要です。誤設定時は最後の読戻しが失敗しますが、既に両サービスと Release が公開されている可能性があるため、再実行で修復しないでください。

GitHub 公式: [release immutability](https://docs.github.com/en/code-security/how-tos/secure-your-supply-chain/establish-provenance-and-integrity/prevent-release-changes)。

### 2. AWS OIDC（永続 AWS key は登録しない）

AWS に GitHub OIDC provider `https://token.actions.githubusercontent.com` / audience `sts.amazonaws.com` と専用 role を用意します。
この repo について管理者が実 API で確認した OIDC customization は次です。

- `use_default=true`, `use_immutable_subject=true`
- `sub_claim_prefix=repo:shinjo15@106855531/HIBILIO_backend@1353313340`
- repo 作成日時: `2026-09-01T08:18:45Z`

trust は **legacy subject `repo:shinjo15/HIBILIO_backend:environment:production` ではなく**、次の immutable subject 完全一致にします。`AWS_ACCOUNT_ID` だけを実 AWS account ID に置換してください。repo / owner の ID は置換しません。provider や subject の設定はこの workflow では変更しません。

```json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Principal": {"Federated": "arn:aws:iam::AWS_ACCOUNT_ID:oidc-provider/token.actions.githubusercontent.com"},
    "Action": "sts:AssumeRoleWithWebIdentity",
    "Condition": {"StringEquals": {
      "token.actions.githubusercontent.com:aud": "sts.amazonaws.com",
      "token.actions.githubusercontent.com:sub": "repo:shinjo15@106855531/HIBILIO_backend@1353313340:environment:production"
    }}
  }]
}
```

role の権限は次だけにします。`EXACT_INSTANCE_ARN` は管理者が `hibilio-production-api` の画面/API で確認した **東京リージョンの Instance UUID を含む実 ARN** に置換します。instance 名を ARN の UUID 欄へ入れないでください。別 policy や broad managed policy は付けません。

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "ReadInstanceAndFirewallInTokyo",
      "Effect": "Allow",
      "Action": ["lightsail:GetInstance", "lightsail:GetInstancePortStates"],
      "Resource": "*",
      "Condition": {"StringEquals": {"aws:RequestedRegion": "ap-northeast-1"}}
    },
    {
      "Sid": "OnlyApprovedInstanceFirewallIncrementalChanges",
      "Effect": "Allow",
      "Action": ["lightsail:OpenInstancePublicPorts", "lightsail:CloseInstancePublicPorts"],
      "Resource": "EXACT_INSTANCE_ARN",
      "Condition": {"StringEquals": {"aws:RequestedRegion": "ap-northeast-1"}}
    }
  ]
}
```

Lightsail の GetInstance / GetInstancePortStates は resource-level IAM 制限に非対応のため、この読取だけ `Resource: "*"` が必要です。コードでは全 operation の region / instance 名も固定します。書込は実 instance ARN に限定し、`PutInstancePublicPorts`、instance 作成/停止、DB、snapshot、DNS、他 instance の書込権限はありません。IAM は port/CIDR 条件を提供しないため、port22 / runner IPv4 /32 の制限は coordinator 側でも実施します。
公式 [Lightsail IAM action/resource 対応](https://docs.aws.amazon.com/IAM/latest/UserGuide/list_lightsail.html)。

role session は1時間、workflow timeout は45分。GitHub-hosted runner だけを使用し、本番 self-hosted runner は設置しません。アプリが既に使っている `/etc/hibilio/aws.env` はこの CI 用 OIDC とは別で、変更しません。

### 3. Cloudflare（既存 Git integration の自動 production を止める）

Cloudflare の **Workers & Pages → hibilio-web → Settings → Builds & deployments** の production branch 設定で、branch が `main` であることを確認し、**Enable automatic production branch deployments** を OFF にして保存します。UI により **Build → Branch control** 内の場合があります。別 project 作成、repo 再接続、DNS 変更は不要です。
[公式 branch control 手順](https://developers.cloudflare.com/pages/configuration/branch-build-controls/)。

管理者が read API `GET /accounts/{account_id}/pages/projects/hibilio-web` でも確認します（token は env から渡し、応答全体や token をログへ出さない）。coordinator は以下を厳密に確認し、変更 API は呼びません。

- project `name=hibilio-web`, `production_branch=main`
- `source.type=github`
- `source.config.owner=shinjo15`, `repo_name=HIBILIO_web_frontend`
- `source.config.production_branch=main`
- `source.config.production_deployments_enabled=false`（欠落・true は拒否）
- `source.config.deployments_enabled=false` は許可します。これは全自動 build の停止で、direct upload の禁止ではありません。
- 既存 `canonical_deployment.id` が存在すること。

対象 account の **Cloudflare Pages: Edit** の専用 API token を作成します。可能な範囲で account を対象の一つに限定します。Global API Key は使いません。Pages token の権限は project 単位に絞れないため、コードでも project 名を固定しています。read API 認証は事前確認しますが、実 Edit 権限は初回管理者確認と実 upload 検証が必要です。
既存 Git-integrated project にも Wrangler direct upload が利用できます（[公式](https://developers.cloudflare.com/pages/configuration/git-integration/#disable-automatic-deployments)）。自動 production を止める前に coordinator を使わないでください。

### 4. 専用 SSH key と root 固定 script

この設定は Lightsail の管理者 SSH / browser SSH から一度だけ行います。通常の管理者接続を残し、新しい経路の認証確認が終わるまで切断しないでください。

- Python 3、Docker、Compose v2.30以降が必要です。root の既存 registry 認証 `/etc/hibilio/docker` から両 private image を pull できることを別途確認します。CI の GH token をサーバーへ送信しません。
- 専用 user `hibilio-deploy` を作成し、password login を無効化します。docker group、通常 sudo、他の SSH key、user-writable authorized_keys を与えません。shell は forced command 起動のため `/bin/sh` 等が必要ですが、自由な shell は許可しません。
- 管理者がレビュー済みの `scripts/release/remote_deploy.py` の内容/ハッシュを確認し、管理者用経路で `/usr/local/sbin/hibilio-deploy` へ root:root / 0755 として配置します。shebang は `/usr/bin/python3 -I` です。親 directory も root 所有・group/other write 不可にします。deploy request から script 自体を置換する仕組みはありません。script 更新は今後も管理者が別途レビューして行います。
- `/opt/hibilio` とその release/state 領域、`/etc/hibilio`、compose.env、Docker config は root 管理にします。現在の構成が異なる場合は停止して確認し、アプリ全体への再帰 chown や移動で無理に通さないでください。
- `/etc/ssh/authorized_keys/hibilio-deploy` を **root:root / 0644** とし、専用 public key 一つだけを置きます。root 所有 0600 では認証対象 user が読めず、実際に SSH public-key 認証が失敗しました。公開鍵なので読取りを許可しますが、編集は root だけです。親 directory も root 所有・group/other write 不可とし、認証対象 user が走査できる権限にします。private key や秘密 env を 0644 にする指示ではありません。`PUBLIC_KEY` は生成した公開鍵の type と base64 に置換します。

```text
restrict,command="/usr/bin/sudo -n /usr/local/sbin/hibilio-deploy" PUBLIC_KEY
```

sudoers は `visudo -f /etc/sudoers.d/hibilio-deploy` で次を登録します。末尾の `""` は **引数なしだけ**の指定です。任意 Python、shell、Docker、script への追加引数や `SETENV` は許可しません。SSH_ORIGINAL_COMMAND は拒否判定のためだけに保持し、補間・eval・実行しません。

```text
Defaults:hibilio-deploy env_reset, !setenv, secure_path="/usr/sbin:/usr/bin:/sbin:/bin"
Defaults!/usr/local/sbin/hibilio-deploy env_keep += "SSH_ORIGINAL_COMMAND"
hibilio-deploy ALL=(root) NOPASSWD: /usr/local/sbin/hibilio-deploy ""
```

sshd の専用 user Match block（既存管理者へ適用しない）も設定します。key の forced command と併用します。

```text
Match User hibilio-deploy
    AuthorizedKeysFile /etc/ssh/authorized_keys/%u
    AuthenticationMethods publickey
    PasswordAuthentication no
    KbdInteractiveAuthentication no
    ForceCommand /usr/bin/sudo -n /usr/local/sbin/hibilio-deploy
    DisableForwarding yes
    AllowTcpForwarding no
    X11Forwarding no
    PermitTunnel no
    PermitTTY no
    PermitUserRC no
```

`sudo visudo -cf /etc/sudoers.d/hibilio-deploy`、`sudo sshd -t` と `sudo sshd -T -C user=hibilio-deploy,host=localhost,addr=127.0.0.1` で構文・適用結果を確認してから reload します。既存設定と競合する場合は停止します。

専用 private key は GitHub environment secret へ入れます。host key は管理者側で `/etc/ssh/ssh_host_ed25519_key.pub` と fingerprint を確認し、独立した経路で照合します。`ssh-keyscan` の結果を無確認で信頼しません。known_hosts secret は `DEPLOY_HOST の IPv4 ssh-ed25519 公開hostkey-base64` の1行（comment なし）です。client は strict pinning / BatchMode / IdentitiesOnly / no PTY / command なしで接続し、bounded JSON を stdin へ送信します。

script は4KiB上限・重複key拒否・exact schema、固定 GHCR namespace と raw GitHub repo だけを許可します。requested backend SHA が本 repo の trusted main ancestry かを **root script 自身が GitHub compare で確認**し、同じ commit の Compose を固定 URL から取得して checksum を確認します。caller の checksum だけでは信頼しません。main にない PR commit、任意 path/root mount 用 Compose、任意 command、script 更新、rollback request は受け付けません。pull 後の OCI revision / platform も独立確認します。root script による任意 main ancestry の再配備を許す鍵なので、盗難時はこの専用 key を即失効してください。main 自体と GHCR write 権限も信頼境界です。

初回 `status` は root Compose labels から現在の2つの file path を検出し、`/opt/hibilio` 配下・root 所有・非 symlink、trusted Compose checksum、project / running digest / health を確認します。`current` symlink は不要で、既存サービスや設定を作り直しません。未知の directory、変更済み Compose、不健康/複数コンテナなら fail-stop します。

承認済み初期値:

```text
backend e3facf736b251f25217ab15a4ff95458a0463fa0
app sha256:28947faabf479ff56409e1ed184f986a5b6693dc8373c00e752ef27da0b76af2
nginx sha256:7518fe5434a24d43b7a79e214336397d410fe4167be09db4f573c57516c68a81
project hibilio-production
root Docker config /etc/hibilio/docker
```

専用経路で `{"operation":"status"}` だけを stdin に渡した read-only 接続を確認します。`id` / shell / SCP / forwarding / PTY / 不正 JSON が拒否されること、通常の管理者接続と browser SSH が引き続き使えることも実機確認します。status でも lock 用 `/run/hibilio-deploy.lock` は使用しますが、Compose/env/release は変更しません。

### 5. production environment の Secrets / Variables

| 種別 | 名前 | 内容 |
|---|---|---|
| Variable | `AWS_ROLE_ARN` | 上記専用 OIDC role ARN |
| Variable | `CLOUDFLARE_ACCOUNT_ID` | 既存 Pages account の32桁hex ID |
| Variable | `DEPLOY_HOST` | 対象 instance の固定 public IPv4（APIと照合） |
| Secret | `CLOUDFLARE_API_TOKEN` | 対象 account の Pages Edit token |
| Secret | `DEPLOY_SSH_KEY` | 専用・passphraseなしの private key |
| Secret | `DEPLOY_KNOWN_HOSTS` | 独立照合した ed25519 host key の1行 |

SSH user はコードで `hibilio-deploy` 固定です。`GITHUB_TOKEN` は自動発行されるものだけを利用します（contents write / actions read / packages read、OIDC 用 id-token write）。別 repo write PAT / AWS access key / server DB secrets は登録しません。既存 private GHCR の Actions read access は、既存 image workflow の準備に従います。

## 通常の実行

1. 両 repo の main CI と backend **Production images** が対象 exact SHA で成功済みであること、migration / API が前後方互換であること、既存 DB バックアップの復旧手段を確認します。現 frontend main は `c723e62ddc2c41a859136557c8e2aafda487ded0`（本手順作成時点）。実行時は API で再解決します。
2. 他の deploy / image tag 再push / Pages手動操作 / firewall編集を止め、backend **Actions → Manual coordinated release → Run workflow → main** を選び、未使用 version（初回例 `v1.0.1`）を入力します。branch/environment は変更しません。
3. coordinator が両 main SHA、最新 exact-main workflow と必須 job の成功、両 package の private/owner/repo、digest/revision/platform、Pages auto-production OFF、credentials の存在・read認証・SSH key形式を確認します。frontend は lockfile を使い Node22、`VITE_API_ORIGIN=https://api.hibilio.com` で先にビルドします。ビルド subprocess へ deploy credentials を渡さず、Wrangler `4.157.0` も事前導入します（アプリ依存は変えません）。
4. OIDC credentials を取得し、SHA ancestry / CI / digest / Compose / build hash / Pages現在版 / instance を再確認してから、runner IPv4 `/32` の TCP22 だけを追加します。既存許可で到達可能な場合は追加・削除しません。read-only remote status / SSH認証の実検証はこの一時許可後になります。権限の存在・形式・API read認証を先に確認しても、root registry pull や各 provider の write 権限の実行成功を保証するものではありません。
5. tag を予約し、draft Release に旧状態を記録します。root は old compose.env を0600で保存し、trusted Compose を `/opt/hibilio/releases/FULL_SHA/` に固定保存します。exact digest で app/nginx を pull して revision を確認後、compose.env の **APP_IMAGE / NGINX_IMAGE のみ**を原子的に変更します。外部 env / TLS / CA / AWS settings 等は変更しません。
6. **新 image** で `run --rm --no-deps --pull never -T app php artisan migrate --force --no-interaction` を明示実行します。seed はしません。app/nginx だけを `up --no-deps --force-recreate --wait` し、実稼働 digest と health を別読戻しします。Redis は recreate/restart/pull しません。
7. backend 成功後にだけ、既存 Pages へ Wrangler `pages deploy ... --branch main --commit-hash FULL_FRONTEND_SHA` で direct upload します。production の deployment ID / commit / branch / success と、deployment 固有 URL・`https://hibilio-web.pages.dev/release.json` の marker（version、両 SHA、image digests）を readback します。公開 custom domain の経路は初回実機確認も行ってください。
8. **この実行が追加した rule だけ**を閉じ、元の IPv4/IPv6/browser SSH alias/443 を含む全 baseline と比較します。両系の確認と cleanup 成功後に、最終 `release.json` asset を保存・digest確認して Release を公開し、immutable flag と本文を読戻します。

Concurrency はサービス全体で1本、`cancel-in-progress: false` です。稼働中の deployment をキャンセルしないでください。port 操作は incremental Open/Close のみ。AWS には per-rule ownership ID がないため、追加前に未存在の exact `/32` を journal に先に記録して所有を定義します。他の管理者が同時に同一 rule を編集しない運用も必要です。既存 rule は所有しません。差分不一致でも mass put/restore はしません。

## 部分失敗・手動復旧

- workflow artifact `coordinated-release-RUN_ID-RUN_ATTEMPT` に selection/build/manifest、sanitized `firewall-recovery.json` と prebuilt dist を14日保持します。recovery file は `owned` / `cleaned`、正規化した元 baseline の `baseline_sha256`、`owned=true` の場合だけ `owned_runner_cidr`（この実行が所有する runner IPv4 `/32`）を含みます。既存の IPv4 / IPv6 / alias は含めず、`owned=false` なら runner と既存許可の IP が同一でも CIDR を記録しません。raw `firewall.json` は既存 cleanup 用に runner-local にだけ残し、upload しません。draft Release の本文にも段階ごとの manifest を保存します。backend は `/opt/hibilio/deployments/VERSION.json`、旧 env は `/opt/hibilio/deployments/VERSION.compose.env`、現状態は `/opt/hibilio/current.json` を保持します（root専用）。秘密 env backup は artifact や Release へ送信しません。
- backend 失敗時は frontend upload しません。frontend upload/marker/cleanup 失敗時は backend が新しくなっていても Release は draft のままです。通信切断では「APIが未実行」と決めつけません。`backend-started` / `frontend-started` から実際には成功していた可能性があります。
- SIGTERM/例外の finally と独立した always step で cleanup しますが、runner 消失 / 強制kill / credentials期限切れは完全保証できません。cleanup 失敗時も finally と独立した always export step で sanitized recovery file を生成します（journal がない場合は生成しません）。cleanup の失敗は成功扱いにしません。必ず `firewall-recovery.json` と実 firewall を確認します。`owned=true` かつ `owned_runner_cidr` が残る場合にだけ、管理者が同じ instance の TCP22 / exact CIDR を incremental close し、正規化した現 firewall の SHA-256 を `baseline_sha256` と比較します。正規化は `rule_atoms` の重複排除・sort 後、JSON を `separators=(',', ':')` で UTF-8 化する方式です。元 baseline 自体は artifact から復元できません。不一致時は管理者側の既存設定記録とも照合し、mass restore はしません。owned=false の rule、browser alias、Cloudflare443、全 rule の put は触りません。
- root の failed/prepared/migrating/recreating record がある間、新規配備も止めます。script は DB down migration や旧 image 自動復帰をしません。migration_started=true なら、migration_applied=false でも DB に一部変更がある可能性があります。DB rollback/fresh/seed を実行しないでください。

管理者は通常の管理者 SSH から以下を判断します。専用 deployment key では復旧用の shell を実行できません。

1. 次のリリースを止め、run artifact、draft/tag、root record の previous / compose_files / migration flags、現在の container digest/health を確認します。raw env、認証、ログの秘密を共有しません。SSH切断後も root 処理が動いていないか lock/プロセスを確認します。
2. 原則は互換性を保った新 version の roll-forward を検討します。旧 backend への復帰が必要なら、**現在の DB schema/data と旧 image の互換性を明示承認した後だけ**、記録された previous の両 digest と両 Compose path を使います。旧 compose.env backup を root0600 の staging file に複製し、APP_IMAGE / NGINX_IMAGE だけを previous の digest 指定へ置き換えてから同じ filesystem 上で compose.env へ rename します。旧 tag は再push可能なので、backup 内の tag をそのまま信頼しません。現在の失敗 config も別の root0600 file に保全します。
3. 以下は管理者 SSH 上の復帰用コマンド形です。`PREVIOUS_COMPOSE_*` の2箇所を root record の previous.compose_files の exact path に置換します。復帰対象 digest を上記 staging で設定済みであることを再確認してから実行します。migration や Redis の操作は含めません。

```sh
sudo docker --config /etc/hibilio/docker compose --project-name hibilio-production --env-file /etc/hibilio/compose.env -f PREVIOUS_COMPOSE_BASE_PATH -f PREVIOUS_COMPOSE_PRODUCTION_PATH config --quiet
sudo docker --config /etc/hibilio/docker compose --project-name hibilio-production --env-file /etc/hibilio/compose.env -f PREVIOUS_COMPOSE_BASE_PATH -f PREVIOUS_COMPOSE_PRODUCTION_PATH up --detach --no-build --no-deps --pull never --force-recreate --wait --wait-timeout 180 app nginx
```

4. Pages に新 deployment が実際にあるか、canonical ID と公開 release.json を確認します。以前へ戻す必要がある場合は、backend/API互換性を確認して **既存 project の Deployments から manifest.previous_pages_deployment_id の production 版に明示 rollback** します。DB の巻戻しにはなりません。API失敗だけを根拠に別 deployment を消さないでください。必要なら保存済み dist で管理者が適切な版を direct upload します。
5. 両サービスの採用状態・DB互換性・health・公開 marker・firewall を管理者が実確認してから、root current.json をその実状態へ合わせ、失敗した VERSION.json に `phase=recovered` と判断理由・実際の採用状態を追記します。record と backup は削除せず、version は予約済みのままです。この管理者操作をしないと次の deploy は拒否されます。draft Release も部分失敗と最終採用状態を明記し、成功版を装って公開しません。次回は新 version を使います。

最終公開 API の応答喪失、immutability未設定、artifact/upload障害などの場合、Release 自体が既に公開されている可能性もあります。tag / asset digest / body / immutable flag を読戻してから対応します。自動再試行で同じ version を使い直さないでください。

### Release メタデータ不一致時の扱い

Release の公開後確認が失敗しても、本番配備まで失敗したとは限りません。配備版の判断では **対象 Git tag の SHA、実稼働 backend の状態、frontend deployment / 公開 release.json marker** を照合し、Release の名前・tag metadata・本文だけを根拠にしません。添付 manifest の digest と完了状態も確認します。

修正版は全 Release PATCH で期待する `tag_name` / `target_commitish` を明示します。作成後・record 前後・publish の asset upload 前と公開 PATCH 前に ID / tag / target / draft / immutable を検証し、公開後も ID / tag / target / body / immutable を読戻します。公開済み・immutable・identity 不一致の Release は `record()` で更新せず、公開後 readback 失敗でも本文を失敗 manifest で上書きしません。実サーバーの同時変更を原子的に防ぐ仕組みではないため、既存の他操作禁止運用も必要です。

[公式 update-release 契約](https://docs.github.com/en/rest/releases/releases?apiVersion=2022-11-28#update-a-release)では `tag_name` は任意で、既存 Git tag がある場合 `target_commitish` は tag 作成に使われません。省略で `untagged-*` になるとは記載されていません。省略時に観測済み症状を返すローカル HTTP 模擬サーバーで回帰を検証しており、省略を実障害の唯一の原因と確定したものではありません。修正版の実 GitHub publication は未検証です。

[immutable release の公式制約](https://docs.github.com/en/code-security/concepts/supply-chain-security/immutable-releases)により、公開済み Release の tag を付け替えて修復しません。既存 Release / tag / asset を削除せず、CD の再実行や新 version の消費も行いません。正しい既存 tag に別の Release を作成するメタデータのみの復旧は、通常 CD の予約済み版再利用とは別の管理者操作です。対象 tag に既存 Release がないこと、SHA・元 asset の digest・本番 marker の一致、公開イベントの副作用を確認し、承認後に実施します。root の配備記録は書き換えません。

## ローカル検証と実機未検証の区別

repo root、Python stdlib のみ:

```sh
python3 -B -m unittest discover -s tests/Infrastructure -p 'test_*.py' -v
actionlint .github/workflows/manual-release.yml
```

tests の API/外部command adapters は明示的な simulation です。実 filesystem の config保存/状態遷移、strict schema、forced-command入力拒否、main ancestry拒否、CI/revision不一致、immutable version、firewall所有/応答喪失cleanup、backend失敗時のfrontend抑止、両系成功後のみ公開、手動reconciliationとrollback限界を検証します。実AWS/SSH/registry/Pages/Releaseの成功を意味しません。

初回 bootstrap 後に別途確認する項目: immutable OIDC subject での role取得、environment の main以外拒否、Pages設定のread API、専用key制限・host pin・root status、実 private digest pull、migration/health、既存 Pages direct upload と公開 marker、両系版の対応、Release immutable asset、実 firewall の追加/削除と既存 SSH/browser/443 の保持。初回本番実行はユーザーと承認して行います。
