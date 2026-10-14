# ER図・テーブル構成

全体を1枚にまとめた編集用ファイル：[draw.io形式でダウンロード](er-diagram.drawio)。draw.ioの「ファイル → ファイルを開く → デバイス」から開いてください。

公開版 Solo Backoffice のマイグレーションを最後まで適用した状態を表しています。実データは含みません。

## 図の読み方

- `PK`：主キー、`FK`：DBに定義された外部キー、`UK`：単一カラムの一意制約。
- `||`：必ず1件、`o|`：0または1件、`o{`：0件以上。親に対する子の件数はDB制約で表しています。
- 点線は非識別関係です。子テーブルは独立した主キー`id`を持ち、外部キーを主キーに含めません。点線もDBの外部キーを表し、アプリ上だけの参照とは区別しています。
- 図では共通の`created_at`・`updated_at`を省略しています。ここに掲載する業務テーブルには両方のカラムがあります。
- 型名は読みやすい表記に簡略化しています。桁数・デフォルト値・削除時の動作など、厳密なDDLは[マイグレーション](../database/migrations)を参照してください。

## 1. 納品・入金

`lumber_invoices`は画面上の「納品書」です。月次請求書は納品データを集計して作成し、専用の月次請求書テーブルはありません。

取引先別入金と納品書別入金は別のテーブルで、両者を結ぶ外部キーはありません。

```mermaid
erDiagram
    clients ||..o{ lumber_invoices : "client_id"
    lumber_invoices ||..o{ lumber_invoice_lines : "lumber_invoice_id"
    lumber_species ||..o{ lumber_invoice_lines : "lumber_species_id"
    lumber_invoice_lines ||..o{ lumber_invoice_line_tiers : "lumber_invoice_line_id"
    lumber_invoices ||..o{ lumber_invoice_payments : "lumber_invoice_id"
    clients ||..o{ client_payments : "client_id"
    lumber_invoices ||..o{ lumber_invoice_product_lines : "lumber_invoice_id"
    products o|..o{ lumber_invoice_product_lines : "product_id"
    clients {
        bigint id PK
        varchar name
        varchar honorific
        varchar postal_code "NULL可"
        varchar address "NULL可"
        varchar contact_person "NULL可"
        varchar email "NULL可"
        varchar phone "NULL可"
        text memo "NULL可"
        varchar invoice_registration_number "NULL可"
    }
    lumber_invoices {
        bigint id PK
        varchar invoice_number UK
        bigint client_id FK
        date issue_date
        enum status
        text notes "NULL可"
        bigint subtotal "符号なし"
        bigint tax_amount "符号なし"
        bigint total "符号なし"
        bigint paid_amount "符号なし"
        bigint subtotal_8 "符号なし"
        bigint tax_8 "符号なし"
        int invoice_adjustment_amount
        varchar invoice_adjustment_description "NULL可"
    }
    lumber_invoice_lines {
        bigint id PK
        bigint lumber_invoice_id FK
        bigint lumber_species_id FK
        decimal thickness "精度6 小数2"
        decimal width "精度6 小数2"
        decimal length "精度6 小数2"
        int adjustment_amount
        int total_quantity "符号なし"
        bigint line_total "符号なし"
        smallint sort_order "符号なし"
        int unit_price_adjustment
        int returned_quantity "符号なし"
    }
    lumber_invoice_line_tiers {
        bigint id PK
        bigint lumber_invoice_line_id FK
        varchar tier_label
        int unit_price "符号なし"
        int quantity "符号なし"
        int amount "符号なし"
    }
    lumber_invoice_product_lines {
        bigint id PK
        bigint lumber_invoice_id FK
        bigint product_id FK "NULL可"
        varchar description
        decimal quantity "精度10 小数2"
        int unit_price "符号なし"
        tinyint tax_rate "符号なし"
        int amount "符号なし"
        smallint sort_order "符号なし"
    }
    products {
        bigint id PK
        varchar name
        text description "NULL可"
        int unit_price "符号なし"
        varchar unit
        tinyint tax_rate "符号なし"
        boolean is_reduced_tax
    }
    lumber_species {
        bigint id PK
        varchar name
        enum pricing_type
        int flat_price "NULL可 符号なし"
        boolean apply_thickness_surcharge
        boolean apply_length_adjustment
    }
    lumber_invoice_payments {
        bigint id PK
        bigint lumber_invoice_id FK
        date paid_at
        int amount "符号なし"
        varchar method "NULL可"
        text memo "NULL可"
    }
    client_payments {
        bigint id PK
        bigint client_id FK
        date paid_at
        int amount "符号なし"
        varchar target_month "NULL可"
        varchar method "NULL可"
        text memo "NULL可"
    }
```

## 2. 単価マスタ

厚み加算・数量帯加算・単価設定は計算時に参照する独立マスタです。数量帯別明細はマスタIDではなく、`tier_label`と計算済みの`unit_price`を保存します。

```mermaid
erDiagram
    lumber_species ||..o{ lumber_width_prices : "lumber_species_id"
    lumber_species {
        bigint id PK
        varchar name
        enum pricing_type
        int flat_price "NULL可 符号なし"
        boolean apply_thickness_surcharge
        boolean apply_length_adjustment
    }
    lumber_width_prices {
        bigint id PK
        bigint lumber_species_id FK
        decimal width "精度6 小数2"
        int base_price "符号なし"
    }
    quantity_tier_surcharges {
        bigint id PK
        varchar label
        int min_qty "符号なし"
        int max_qty "NULL可 符号なし"
        int surcharge_amount
        smallint sort_order "符号なし"
    }
    thickness_surcharges {
        bigint id PK
        int min_thickness "符号なし"
        int surcharge_amount
    }
    pricing_settings {
        bigint id PK
        varchar key UK
        varchar value
    }
```

## 3. 勤怠・給与

勤怠と給与支払いは従業員に紐付きます。給与支払いから個別の勤怠行を参照する外部キーはありません。対象月の勤怠を集計し、支払い時の金額・控除額・扶養人数を保存します。

税額表は年・給与額・扶養人数を使って検索するため、給与支払いとの外部キーはありません。`amount`は差引支給額、`gross_amount`は控除前の支給額です。

```mermaid
erDiagram
    employees ||..o{ attendances : "employee_id"
    employees ||..o{ salary_payments : "employee_id"
    employees {
        bigint id PK
        varchar name
        int hourly_wage "符号なし"
        boolean is_active
        tinyint dependents "符号なし"
        varchar employment_type
    }
    attendances {
        bigint id PK
        bigint employee_id FK
        date work_date
        time start_time
        time end_time
        int break_minutes "符号なし"
        int worked_minutes "符号なし"
        int amount "符号なし"
        text memo "NULL可"
    }
    salary_payments {
        bigint id PK
        bigint employee_id FK
        date paid_at
        varchar target_period "NULL可"
        int amount "符号なし"
        enum method
        text memo "NULL可"
        int gross_amount "符号なし"
        int withholding_tax "符号なし"
        int social_insurance "符号なし"
        tinyint dependents "NULL可 符号なし"
        int resident_tax "符号なし"
    }
    withholding_tax_brackets {
        bigint id PK
        int min_amount "符号なし"
        int max_amount "符号なし"
        int dep_0 "符号なし"
        int dep_1 "符号なし"
        int dep_2 "符号なし"
        int dep_3 "符号なし"
        int dep_4 "符号なし"
        int dep_5 "符号なし"
        int dep_6 "符号なし"
        int dep_7 "符号なし"
        smallint tax_year "符号なし"
    }
```

## 4. 会計・共通マスタ

`expense_categories`は名称にexpenseを含みますが、現在は売上・資産・負債なども含む勘定科目マスタです。出納帳・総勘定元帳・決算書は仕訳や期首残高を集計して表示するため、帳票ごとのテーブルはありません。

支払先は入力候補として使われ、仕訳明細には名前を`counterparty_name`として保存します。`payee_id`はありません。事業者情報も帳票出力時に参照する独立テーブルです。

```mermaid
erDiagram
    journal_entries ||..o{ journal_entry_lines : "journal_entry_id"
    expense_categories ||..o{ journal_entry_lines : "expense_category_id"
    expense_categories ||..o{ opening_balances : "expense_category_id"
    journal_entries {
        bigint id PK
        date entry_date
        varchar description "NULL可"
    }
    journal_entry_lines {
        bigint id PK
        bigint journal_entry_id FK
        enum side
        bigint expense_category_id FK
        varchar counterparty_name "NULL可"
        int amount "符号なし"
        text memo "NULL可"
        smallint sort_order "符号なし"
    }
    expense_categories {
        bigint id PK
        varchar name
        smallint sort_order "符号なし"
        varchar statement_type "NULL可"
    }
    opening_balances {
        bigint id PK
        bigint expense_category_id FK
        date effective_date
        int amount
        text memo "NULL可"
    }
    payees {
        bigint id PK
        varchar name
        text memo "NULL可"
    }
    companies {
        bigint id PK
        varchar name
        varchar representative_name "NULL可"
        varchar postal_code "NULL可"
        varchar address "NULL可"
        varchar phone "NULL可"
        varchar email "NULL可"
        varchar invoice_registration_number "NULL可"
        varchar bank_name "NULL可"
        varchar bank_branch "NULL可"
        varchar bank_account_type "NULL可"
        varchar bank_account_number "NULL可"
        varchar bank_account_holder "NULL可"
        varchar seal_image_path "NULL可"
    }
```

## アプリケーション上の仕訳連携（外部キーではない）

| 登録元 | 振替伝票の識別方法 |
| --- | --- |
| `client_payments` | `journal_entries.description`に`入金:client_payment_{id}`を保存 |
| `salary_payments` | `journal_entries.description`に`給与:salary_payment_{id}`を保存 |

モデルの`syncJournalEntry()`・`deleteJournalEntry()`が、この文字列で対応する伝票を検索します。DBが1対1を保証する関係ではないため、ER図に外部キーの線は引いていません。納品書別の`lumber_invoice_payments`には同じ自動仕訳メソッドはありません。

## 複合一意制約と入力チェック

| 対象 | 制約・検証 |
| --- | --- |
| `lumber_width_prices` | DBの複合一意制約：`lumber_species_id`＋`width` |
| `opening_balances` | DBの複合一意制約：`expense_category_id`＋`effective_date` |
| `attendances` | 同一従業員・勤務日の重複はアプリの入力検証で防止（DBの複合一意制約はなし） |
| `salary_payments` | 同一従業員・対象月の重複はアプリの入力検証で防止（DBの複合一意制約はなし） |

## フレームワーク用テーブル・削除済みテーブル

業務ER図からは、Laravel標準の`users`、`password_reset_tokens`、`sessions`、`cache`、`cache_locks`、`jobs`、`job_batches`、`failed_jobs`およびマイグレーション管理用の`migrations`を省略しています。認証画面は現状未実装です。`sessions.user_id`にはインデックスはありますが、DBの外部キー制約はありません。

旧支出管理の`expenses`は後続マイグレーションで削除されるため、現行ER図に含めていません。

## テーブル名と用途

| テーブル | 用途 |
| --- | --- |
| `companies` | 事業者情報 |
| `clients` | 取引先 |
| `products` | 品目 |
| `lumber_species` | 樹種 |
| `lumber_width_prices` | 幅別単価 |
| `quantity_tier_surcharges` | 数量帯加算 |
| `thickness_surcharges` | 厚み加算 |
| `pricing_settings` | 単価設定 |
| `lumber_invoices` | 納品書 |
| `lumber_invoice_lines` | 木材明細 |
| `lumber_invoice_line_tiers` | 数量帯別明細 |
| `lumber_invoice_product_lines` | 品目明細 |
| `lumber_invoice_payments` | 納品書別入金 |
| `client_payments` | 取引先別入金 |
| `employees` | 従業員 |
| `attendances` | 勤怠 |
| `salary_payments` | 給与支払い |
| `withholding_tax_brackets` | 源泉徴収税額表 |
| `journal_entries` | 振替伝票 |
| `journal_entry_lines` | 仕訳明細 |
| `expense_categories` | 勘定科目 |
| `opening_balances` | 期首残高 |
| `payees` | 支払先 |

旧見積・請求テーブル（`estimates`、`estimate_items`、`invoices`、`invoice_items`、`payments`）は、整理用マイグレーションですべて空であることを確認して削除します。1件でもデータがあれば、削除開始前に処理を中止します。作成時のマイグレーションは既存環境の履歴とロールバックのため保持しています。
