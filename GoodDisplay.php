<?php
/**
 * mailorder 商品詳細画面（買い物かご）
 * 全リンク相対パス。
 */
session_start();
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

$gid = isset($_GET['gid']) ? trim((string)$_GET['gid']) : '';
$cid = isset($_GET['cid']) ? trim((string)$_GET['cid']) : '';
$q   = isset($_GET['q'])   ? trim((string)$_GET['q'])   : '';

$conn = connectDb();
$goods = null;
$error = null;

if (!$conn) {
    $error = $GLOBALS['db_error'] ?? 'データベースに接続できません。';
} elseif ($gid === '' || !ctype_digit($gid)) {
    $error = '商品IDが正しく指定されていません。';
} else {
    $sql = "SELECT g.GoodsID, g.GoodsName, g.PRICE, g.Stock, g.ImageName,
                   m.MakerName, c.CategoryName
            FROM Goods g
            LEFT JOIN Maker m ON g.MakerID = m.MakerID
            LEFT JOIN GoodsCategory c ON g.CategoryID = c.CategoryID
            WHERE g.GoodsID = ?
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        $error = mysqli_error($conn);
    } else {
        $gidInt = (int)$gid;
        mysqli_stmt_bind_param($stmt, 'i', $gidInt);
        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
        } else {
            $res = mysqli_stmt_get_result($stmt);
            if ($res) {
                $goods = mysqli_fetch_assoc($res);
                mysqli_free_result($res);
            }
            if (!$goods) {
                $error = '該当する商品が見つかりません。';
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}

$cartError = null;
$cartMessage = null;
$qtyValue = '1';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!$error && $goods && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtyRaw = trim((string)($_POST['qty'] ?? ''));
    $qtyValue = $qtyRaw;
    $stockVal = (int)($goods['Stock'] ?? 0);

    if ($qtyRaw === '') {
        $cartError = '個数を入力してください。';
    } elseif (!preg_match('/^-?\d+$/', $qtyRaw)) {
        $cartError = '個数は数字で入力してください。';
    } else {
        $qtyInt = (int)$qtyRaw;
        if ($qtyInt <= 0) {
            $cartError = '個数は1以上で入力してください。';
        } elseif ($qtyInt >= $stockVal) {
            $cartError = '在庫数以上の個数は指定できません。';
        } else {
            $cartMessage = '買い物かごに追加しました。';
            $_SESSION['cart'][(string)$gid] = $qtyInt;
        }
    }
}

$pageTitle = '商品詳細';
$backUrl = 'CategorySearch.php';
$backParams = [];
if ($cid !== '') $backParams[] = 'cid=' . rawurlencode((string)$cid);
if ($q !== '') $backParams[] = 'q=' . rawurlencode((string)$q);
if (count($backParams) > 0) {
    $backUrl .= '?' . implode('&', $backParams);
}

$imageSrc = '';
if (!$error && $goods) {
    $imageName = trim((string)($goods['ImageName'] ?? ''));
    if ($imageName !== '') {
        if (preg_match('#^https?://#i', $imageName)) {
            $imageSrc = $imageName;
        } elseif (strpos($imageName, 'goodsImg/') === 0 || strpos($imageName, '/') === 0) {
            $imageSrc = $imageName;
        } else {
            $imageSrc = 'goodsImg/' . ltrim($imageName, '/');
        }
    }
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
        </header>

        <?php if ($error) { ?>
            <div class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } else { ?>
            <div class="detail-card">
                <div class="detail-grid">
                    <div>
                        <?php if ($imageSrc !== '') { ?>
                            <img class="detail-image" src="<?php echo htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="商品画像">
                        <?php } else { ?>
                            <div class="detail-image placeholder">画像なし</div>
                        <?php } ?>
                    </div>
                    <ul class="detail-list">
                        <li><span class="detail-label">商品名</span><?php echo htmlspecialchars($goods['GoodsName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><span class="detail-label">カテゴリ</span><?php echo htmlspecialchars($goods['CategoryName'] ?? '未設定', ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><span class="detail-label">メーカー</span><?php echo htmlspecialchars($goods['MakerName'] ?? '未設定', ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><span class="detail-label">単価</span>¥<?php echo number_format((int)($goods['PRICE'] ?? 0)); ?></li>
                        <li><span class="detail-label">在庫</span><?php echo htmlspecialchars((string)($goods['Stock'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="cart-box">
                <h2>買い物かご</h2>
                <?php if ($cartError) { ?>
                    <div class="error-msg"><?php echo htmlspecialchars($cartError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } elseif ($cartMessage) { ?>
                    <div class="success-msg"><?php echo htmlspecialchars($cartMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <form method="post" action="">
                    <label for="qty">個数</label>
                    <input type="text" id="qty" name="qty" value="<?php echo htmlspecialchars($qtyValue !== '' ? $qtyValue : '1', ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit">買い物かご</button>
                </form>
            </div>
        <?php } ?>

        <div class="detail-actions">
            <a class="btn" href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>">検索結果へ戻る</a>
            <a class="btn secondary" href="index.php">トップへ戻る</a>
            <a class="btn secondary" href="Dispaycart.php">カートを見る</a>
        </div>
    </div>
</body>
</html>
