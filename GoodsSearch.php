<?php
/**
 * mailorder 商品名検索結果画面
 * DBスキーマ: query.sql 準拠
 *   Goods(GoodsID, CategoryID, GoodsName, PRICE, Stock, ImageName, MakerID)
 *   Maker(MakerID, MakerName, MakerURL)
 * q=商品名 で検索。未指定なら全件検索。全リンク相対パス。
 */
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

function fetchGoodsByKeyword($conn, $q) {
    $where = ["g.Stock > 0"];
    $types = '';
    $params = [];
    $hasKeyword = false;

    if ($q !== '' && $q !== null) {
        $where[] = "g.GoodsName LIKE ?";
        $types .= 's';
        $params[] = '%' . $q . '%';
        $hasKeyword = true;
    }

    $sql = "SELECT g.GoodsID, g.GoodsName, g.PRICE, g.Stock, g.ImageName, m.MakerName
            FROM Goods g
            LEFT JOIN Maker m ON g.MakerID = m.MakerID
            WHERE " . implode(" AND ", $where) . "
            ORDER BY m.MakerName, g.GoodsName";

    if (!$hasKeyword) {
        $res = mysqli_query($conn, $sql);
        if (!$res) return [[], mysqli_error($conn)];
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return [[], mysqli_error($conn)];
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) return [[], mysqli_stmt_error($stmt)];
        $res = mysqli_stmt_get_result($stmt);
        if (!$res) return [[], mysqli_stmt_error($stmt)];
    }

    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = $r;
    }
    if ($res) mysqli_free_result($res);
    return [$rows, null];
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$conn = connectDb();
$goodsRows = [];
$error = null;

if (!$conn) {
    $error = $GLOBALS['db_error'] ?? 'データベースに接続できません。';
} else {
    list($goodsRows, $err) = fetchGoodsByKeyword($conn, $q);
    if ($err) $error = $err;
    mysqli_close($conn);
}

$resultCount = count($goodsRows);
$pageTitle = '検索結果';

function buildDetailUrl($gid, $q) {
    $url = 'GoodDisplay.php?gid=' . rawurlencode((string)$gid);
    if ($q !== '') {
        $url .= '&q=' . rawurlencode((string)$q);
    }
    return $url;
}
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

        <a href="index.php" class="back-link">← トップへ戻る</a>

        <?php if ($error) { ?>
            <div class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } else { ?>
            <div class="search-info">
                <?php if ($q !== '') { ?>
                    <p>キーワード「<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>」で検索</p>
                <?php } else { ?>
                    <p>全件検索結果（キーワード指定なし）</p>
                <?php } ?>
                <p class="result-count">検索結果: <?php echo $resultCount; ?>件</p>
            </div>

            <?php if ($resultCount > 0) { ?>
                <table>
                    <thead>
                        <tr>
                            <th>メーカー</th>
                            <th>商品名</th>
                            <th>単価</th>
                            <th>在庫</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $prevMaker = '';
                        foreach ($goodsRows as $row) {
                            $maker = $row['MakerName'] ?? '';
                            $dispMaker = ($maker === $prevMaker) ? '' : $maker;
                            $prevMaker = $maker;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dispMaker, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['GoodsName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>¥<?php echo number_format((int)($row['PRICE'] ?? 0)); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['Stock'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a class="detail-btn" href="<?php echo htmlspecialchars(buildDetailUrl($row['GoodsID'], $q), ENT_QUOTES, 'UTF-8'); ?>">詳細</a></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p class="no-data">該当する商品はありません。</p>
            <?php } ?>
        <?php } ?>
    </div>
</body>
</html>
