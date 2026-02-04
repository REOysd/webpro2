<?php
/**
 * mailorder カート表示画面
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

function buildImageSrc($imageName) {
    $imageName = trim((string)$imageName);
    if ($imageName === '') return '';
    if (preg_match('#^https?://#i', $imageName)) return $imageName;
    if (strpos($imageName, 'goodsImg/') === 0 || strpos($imageName, '/') === 0) return $imageName;
    return 'goodsImg/' . ltrim($imageName, '/');
}

$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart)) $cart = [];

$error = null;
$rowErrors = [];
$rowMessages = [];
$items = [];
$totalQty = 0;
$totalAmount = 0;
$missingIds = [];

if (count($cart) > 0) {
    $conn = connectDb();
    if (!$conn) {
        $error = $GLOBALS['db_error'] ?? 'データベースに接続できません。';
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
            $gidInt = 0;
            mysqli_stmt_bind_param($stmt, 'i', $gidInt);
            foreach ($cart as $gidKey => $qtyVal) {
                if (!ctype_digit((string)$gidKey)) continue;
                $qtyInt = (int)$qtyVal;
                if ($qtyInt <= 0) continue;
                $gidInt = (int)$gidKey;
                if (!mysqli_stmt_execute($stmt)) {
                    $error = mysqli_stmt_error($stmt);
                    break;
                }
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                if ($res) mysqli_free_result($res);
                if (!$row) {
                    $missingIds[] = $gidKey;
                    continue;
                }
                $price = (int)($row['PRICE'] ?? 0);
                $lineTotal = $price * $qtyInt;
                $items[] = [
                    'gid' => $gidKey,
                    'name' => $row['GoodsName'] ?? '',
                    'maker' => $row['MakerName'] ?? '',
                    'category' => $row['CategoryName'] ?? '',
                    'price' => $price,
                    'stock' => $row['Stock'] ?? '',
                    'qty' => $qtyInt,
                    'total' => $lineTotal,
                    'image' => buildImageSrc($row['ImageName'] ?? ''),
                ];
                $totalQty += $qtyInt;
                $totalAmount += $lineTotal;
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $action = $_POST['action_override'] ?? ($_POST['action'] ?? '');
    $removeGid = isset($_POST['remove_gid']) ? (string)$_POST['remove_gid'] : '';
    if ($removeGid !== '') {
        if (isset($_SESSION['cart'][$removeGid])) {
            unset($_SESSION['cart'][$removeGid]);
            $cart = $_SESSION['cart'];
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        $cart = [];
        $items = [];
        $totalQty = 0;
        $totalAmount = 0;
    } elseif ($action === 'update') {
        $updates = $_POST['qty'] ?? [];
        if (is_array($updates)) {
            foreach ($items as $idx => $item) {
                $gidKey = (string)$item['gid'];
                if (!array_key_exists($gidKey, $updates)) continue;
                $raw = trim((string)$updates[$gidKey]);
                $stockVal = (int)$item['stock'];
                if ($raw === '') {
                    $rowErrors[$gidKey] = '個数を入力してください。';
                    continue;
                }
                if (!preg_match('/^-?\\d+$/', $raw)) {
                    $rowErrors[$gidKey] = '個数は数字で入力してください。';
                    continue;
                }
                $qtyInt = (int)$raw;
                if ($qtyInt <= 0) {
                    $rowErrors[$gidKey] = '個数は1以上で入力してください。';
                    continue;
                }
                if ($qtyInt >= $stockVal) {
                    $rowErrors[$gidKey] = '在庫数以上の個数は指定できません。';
                    continue;
                }
                $_SESSION['cart'][$gidKey] = $qtyInt;
                $rowMessages[$gidKey] = '個数を更新しました。';
            }
        }
    }
}

// Rebuild items after updates/removals
$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart)) $cart = [];
$items = [];
$totalQty = 0;
$totalAmount = 0;
$missingIds = [];
if (count($cart) > 0) {
    $conn = connectDb();
    if ($conn) {
        $sql = "SELECT g.GoodsID, g.GoodsName, g.PRICE, g.Stock, g.ImageName,
                       m.MakerName, c.CategoryName
                FROM Goods g
                LEFT JOIN Maker m ON g.MakerID = m.MakerID
                LEFT JOIN GoodsCategory c ON g.CategoryID = c.CategoryID
                WHERE g.GoodsID = ?
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $gidInt = 0;
            mysqli_stmt_bind_param($stmt, 'i', $gidInt);
            foreach ($cart as $gidKey => $qtyVal) {
                if (!ctype_digit((string)$gidKey)) continue;
                $qtyInt = (int)$qtyVal;
                if ($qtyInt <= 0) continue;
                $gidInt = (int)$gidKey;
                if (!mysqli_stmt_execute($stmt)) {
                    $error = mysqli_stmt_error($stmt);
                    break;
                }
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                if ($res) mysqli_free_result($res);
                if (!$row) {
                    $missingIds[] = $gidKey;
                    continue;
                }
                $price = (int)($row['PRICE'] ?? 0);
                $lineTotal = $price * $qtyInt;
                $items[] = [
                    'gid' => $gidKey,
                    'name' => $row['GoodsName'] ?? '',
                    'maker' => $row['MakerName'] ?? '',
                    'category' => $row['CategoryName'] ?? '',
                    'price' => $price,
                    'stock' => $row['Stock'] ?? '',
                    'qty' => $qtyInt,
                    'total' => $lineTotal,
                    'image' => buildImageSrc($row['ImageName'] ?? ''),
                ];
                $totalQty += $qtyInt;
                $totalAmount += $lineTotal;
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conn);
    }
}

$pageTitle = '買い物かご';
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

        <a href="index.php" class="back-link">← トップへ戻る</a>

        <?php if ($error) { ?>
            <div class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } elseif (count($items) === 0) { ?>
            <p class="no-data">カートに商品がありません。</p>
        <?php } else { ?>
            <?php if (!empty($missingIds)) { ?>
                <div class="error-msg">商品が見つからないため表示できない商品があります。</div>
            <?php } ?>
            <form method="post" action="">
                <input type="hidden" name="action" value="update">
                <table>
                    <thead>
                        <tr>
                            <th>画像</th>
                            <th>商品名</th>
                            <th>単価</th>
                            <th>個数</th>
                            <th>小計</th>
                            <th>在庫</th>
                            <th>削除</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item) { ?>
                        <tr>
                            <td>
                                <?php if ($item['image'] !== '') { ?>
                                    <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="商品画像" style="max-width:80px; height:auto;">
                                <?php } else { ?>
                                    <span>画像なし</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?><br>
                                <span style="color:#64748b; font-size:0.85rem;">
                                    <?php echo htmlspecialchars($item['maker'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if (!empty($rowErrors[$item['gid']])) { ?>
                                    <div class="row-error"><?php echo htmlspecialchars($rowErrors[$item['gid']], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php } elseif (!empty($rowMessages[$item['gid']])) { ?>
                                    <div class="row-success"><?php echo htmlspecialchars($rowMessages[$item['gid']], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php } ?>
                            </td>
                            <td>¥<?php echo number_format($item['price']); ?></td>
                            <td>
                                <input type="text" class="qty-input" name="qty[<?php echo htmlspecialchars((string)$item['gid'], ENT_QUOTES, 'UTF-8'); ?>]"
                                       value="<?php echo htmlspecialchars((string)$item['qty'], ENT_QUOTES, 'UTF-8'); ?>">
                            </td>
                            <td>¥<?php echo number_format($item['total']); ?></td>
                            <td><?php echo htmlspecialchars((string)$item['stock'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button class="btn secondary small" type="submit"
                                        name="remove_gid" value="<?php echo htmlspecialchars((string)$item['gid'], ENT_QUOTES, 'UTF-8'); ?>">
                                    削除
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div class="cart-actions">
                    <button type="submit" class="btn">個数を変更</button>
                    <button type="submit" class="btn secondary" name="action_override" value="clear">すべて削除</button>
                </div>
            </form>

            <div class="cart-summary">
                <div>合計個数: <?php echo $totalQty; ?> 個</div>
                <div>合計金額: ¥<?php echo number_format($totalAmount); ?></div>
            </div>
        <?php } ?>
    </div>
</body>
</html>
