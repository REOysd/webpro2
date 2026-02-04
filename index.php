<?php
/**
 * mailorder トップページ
 * DBスキーマ: query.sql 準拠
 *   GoodsCategory(CategoryID, CategoryName) をリンク表示。
 * カテゴリクリックで CategorySearch.php?cid=ID へ。商品名検索は CategorySearch.php?q=... 。
 * リンクは日本語を含まない（cid=数値）。全リンク相対パス。
 */
// DB設定は db_config.php でまとめて管理
// env (DB_HOST/DB_PORT/DB_USER/DB_PASS/DB_NAME) があれば優先。
require_once __DIR__ . '/db_config.php';

function connectDb() {
    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    } catch (mysqli_sql_exception $e) {
        $GLOBALS['db_error'] = $e->getMessage() . " (host=" . DB_HOST . " port=" . DB_PORT . ")";
        return null;
    }
    if (!$conn) {
        $GLOBALS['db_error'] = mysqli_connect_error() . " (host=" . DB_HOST . " port=" . DB_PORT . ")";
        return null;
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function fetchCategories($conn) {
    $sql = "SELECT CategoryID, CategoryName FROM GoodsCategory ORDER BY CategoryName";
    $res = mysqli_query($conn, $sql);
    if (!$res) return [];
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }
    mysqli_free_result($res);
    return $rows;
}

$conn = connectDb();
$categories = $conn ? fetchCategories($conn) : [];
$dbError = $conn ? null : ($GLOBALS['db_error'] ?? 'データベースに接続できません。');
if ($conn) mysqli_close($conn);

$pageTitle = '日電通販';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrap">
        <header>
            <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <a class="cart-link" href="Dispaycart.php">カートを見る</a>
        </header>

        <?php if ($dbError !== null) { ?>
            <div class="error-msg"><?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <section class="search-box">
            <h2 style="margin-top:0;">商品名で検索</h2>
            <form method="get" action="GoodsSearch.php">
                <label for="q">商品名（部分一致）</label>
                <input type="text" id="q" name="q" placeholder="商品名を入力">
                <button type="submit">検索</button>
            </form>
        </section>

        <section class="category-section">
            <h2>カテゴリから選ぶ</h2>
            <p>カテゴリをクリックすると検索結果画面に遷移します。</p>
            <ul class="category-list">
                <?php foreach ($categories as $c) { ?>
                    <li>
                        <a href="CategorySearch.php?cid=<?php echo (int)$c['CategoryID']; ?>"><?php echo htmlspecialchars($c['CategoryName'], ENT_QUOTES, 'UTF-8'); ?></a>
                    </li>
                <?php } ?>
            </ul>
            <?php if (empty($categories)) { ?>
                <p class="note">カテゴリがありません。GoodsCategory にデータを挿入するとここに表示されます。</p>
            <?php } ?>
        </section>
    </div>
</body>
</html>
