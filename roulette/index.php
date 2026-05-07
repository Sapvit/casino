<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['history'])) $_SESSION['history'] = [];

// Обработка ввода
if (isset($_POST['number']) && $_POST['number'] !== '') {
    $num = (int)$_POST['number'];
    if ($num >= 0 && $num <= 36) array_unshift($_SESSION['history'], $num);
}
if (isset($_POST['clear'])) $_SESSION['history'] = [];

// Параметры сортировки
$sortCol = $_GET['sort'] ?? 'number';
$sortOrder = $_GET['order'] ?? 'asc';

$probabilities = calculateProbabilities($_SESSION['history'], $groups);
$numberStats = getIndividualNumbersStats($_SESSION['history']);

// Сортировка таблицы
usort($numberStats, function($a, $b) use ($sortCol, $sortOrder) {
    if ($a[$sortCol] == $b[$sortCol]) return 0;
    $res = ($a[$sortCol] < $b[$sortCol]) ? -1 : 1;
    return ($sortOrder === 'asc') ? $res : -$res;
});

function sortLink($col, $currentCol, $currentOrder) {
    $order = ($currentCol === $col && $currentOrder === 'asc') ? 'desc' : 'asc';
    return "?sort=$col&order=$order";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main-layout">
        <div class="panel input-panel">
            <div class="input-static">
                <h2>ВВОД</h2>
                <form method="POST">
                    <input type="number" name="number" min="0" max="36" autofocus required>
                    <button type="submit" class="btn btn-add">ДОБАВИТЬ</button>
                </form>
                <form method="POST">
                    <button name="clear" class="btn btn-clear">СБРОС</button>
                </form>
            </div>

            <div class="history-list">
                <h3>ИСТОРИЯ</h3>
                <?php foreach($_SESSION['history'] as $h): ?>
                    <div class="history-item"><?php echo $h; ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="category-grid">
            <?php 
            $display = [
                'Цвета' => ['Red', 'Black', 'Zero'],
                'Четность' => ['Even', 'Odd'],
                'Половины' => ['1-18', '19-36'],
                'Дюжины' => ['1st Dozen', '2nd Dozen', '3rd Dozen']
            ];
            foreach ($display as $title => $keys): ?>
                <div class="panel stat-group">
                    <h2><?php echo $title; ?></h2>
                    <?php foreach ($probabilities as $p): if (in_array($p['name'], $keys)): ?>
                        <div class="stat-row">
                            <span><strong><?php echo $p['name']; ?></strong> (S:<?php echo $p['streak']; ?>)</span>
                            <div style="text-align: right;">
                                <div style="font-size: 0.75rem; color: #aaa;">След: <?php echo formatProb($p['prob']); ?></div>
                                <div class="val-prob <?php echo getAnomalyClass($p['break']); ?>">
                                    Риск: <?php echo formatExpectation($p['break']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="panel">
            <h2>ЧИСЛА (0-36)</h2>
            <div class="numbers-table-container">
                <table class="numbers-table">
                    <thead>
                        <tr>
                            <th><a href="<?php echo sortLink('number', $sortCol, $sortOrder); ?>">№</a></th>
                            <th><a href="<?php echo sortLink('sigma', $sortCol, $sortOrder); ?>">&Sigma;</a></th>
                            <th><a href="<?php echo sortLink('lastSeen', $sortCol, $sortOrder); ?>" title="Интервал (бросков назад)">&Delta;</a></th>
                            <th><a href="<?php echo sortLink('prob', $sortCol, $sortOrder); ?>">%</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($numberStats as $s): ?>
                            <tr>
                                <td><span class="num-badge"><?php echo $s['number']; ?></span></td>
                                <td><?php echo $s['sigma']; ?></td>
                                <td><?php echo $s['lastSeen']; ?></td>
                                <td class="val-prob"><?php echo formatProb($s['prob']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
