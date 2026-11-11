# セキュリティ

脆弱性の報告に実際のトークン、個人情報、本番設定を含めないでください。公開前に GitHub の Private vulnerability reporting を有効にし、そこから報告できるようにしてください。現時点では窓口は未設定です。

このリポジトリは学習用です。本番導入では HTTPS、秘密管理、監査ログ、トークン失効運用、利用規約・プライバシーポリシー、バックアップを別途設計してください。Docker内のパスワードはローカル専用です。

- 認証: Sanctum Bearer token。有効期限60分。Cookie認証・ログインAPIは未実装。
- 認可: 削除は認証主体のIDをSQL条件に含める。本文から渡されたauthor_idは拒否。
- ID: 投稿にはUUIDを使用。UUIDや暗号化IDは認可の代替にはならない。
- 情報最小化: 内部ユーザーID・メールアドレス・トークンをレスポンスに含めない。
- 入力: FormRequestとValueObjectで境界・ドメイン不変条件をそれぞれ検証。
- SQL: Query Builderのパラメータ束縛。任意カラム名・任意ソートは受け付けない。
- ログ: Authorizationヘッダや本文を独自に記録しない。プロキシ側でもマスクする。
- 依存: CIのcomposer auditとDependabotで確認。依存更新時はcomposer.lockも必ずコミットする。
- CSRF: このAPIはBearer認証専用。SPA Cookie認証を追加する際はCSRF・CORSを再設計する。
- XSS: JSONの本文はプレーンテキスト。利用するUIでは必ずエスケープし、innerHTMLへ直接代入しない。
