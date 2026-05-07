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

// Вспомогательная функция: определить цвет числа
function getNumberColor($num) {
    $red = [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36];
    if ($num === 0) return 'green';
    return in_array($num, $red) ? 'red' : 'black';
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

                <div class="view-toggle-container">
                    <button class="view-toggle active" data-view="table" onclick="switchView('table')">📊 Таблица</button>
                    <button class="view-toggle" data-view="roulette" onclick="switchView('roulette')">🎡 Рулетка</button>
                </div>
            </div>

            <div class="history-list">
                <h3>ИСТОРИЯ</h3>
                <?php foreach($_SESSION['history'] as $h): ?>
                    <div class="history-item"><?php echo $h; ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ТАБЛИЦА -->
        <div id="table-view" class="show">
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

        <!-- РУЛЕТКА -->
        <div id="roulette-view">
            <div class="roulette-table">
                <!-- Главный стол чисел -->
                <div class="roulette-main">
                    <!-- Зеро -->
                    <div class="roulette-zero">
                        <button class="number-btn-large green" onclick="addNumber(0)">
                            <div class="cell-content">
                                <div class="cell-number">0</div>
                                <?php 
                                $zeroStats = array_values(array_filter($numberStats, fn($s) => $s['number'] == 0))[0] ?? null;
                                $zeroProb = $zeroStats ? number_format($zeroStats['prob'], 2, '.', '') : '0';
                                ?>
                                <div class="cell-prob"><?php echo $zeroProb; ?>%</div>
                            </div>
                        </button>
                    </div>

                    <!-- Таблица чисел -->
                    <div class="roulette-numbers">
                        <!-- Строка 1: 3, 6, 9, 12, 15, 18, 21, 24, 27, 30, 33, 36 -->
                        <div class="number-row">
                            <?php for ($i = 3; $i <= 36; $i += 3) {
                                $color = getNumberColor($i);
                                $stats = array_values(array_filter($numberStats, fn($s) => $s['number'] == $i))[0] ?? null;
                                $prob = $stats ? number_format($stats['prob'], 2, '.', '') : '0';
                                echo "<button class='number-btn-cell $color' onclick='addNumber($i)' title='$i' data-prob='$prob'>
                                    <div class='cell-content'>
                                        <div class='cell-number'>$i</div>
                                        <div class='cell-prob'>{$prob}%</div>
                                    </div>
                                </button>";
                            } ?>
                        </div>

                        <!-- Строка 2: 2, 5, 8, 11, 14, 17, 20, 23, 26, 29, 32, 35 -->
                        <div class="number-row">
                            <?php for ($i = 2; $i <= 35; $i += 3) {
                                $color = getNumberColor($i);
                                $stats = array_values(array_filter($numberStats, fn($s) => $s['number'] == $i))[0] ?? null;
                                $prob = $stats ? number_format($stats['prob'], 2, '.', '') : '0';
                                echo "<button class='number-btn-cell $color' onclick='addNumber($i)' title='$i' data-prob='$prob'>
                                    <div class='cell-content'>
                                        <div class='cell-number'>$i</div>
                                        <div class='cell-prob'>{$prob}%</div>
                                    </div>
                                </button>";
                            } ?>
                        </div>

                        <!-- Строка 3: 1, 4, 7, 10, 13, 16, 19, 22, 25, 28, 31, 34 -->
                        <div class="number-row">
                            <?php for ($i = 1; $i <= 34; $i += 3) {
                                $color = getNumberColor($i);
                                $stats = array_values(array_filter($numberStats, fn($s) => $s['number'] == $i))[0] ?? null;
                                $prob = $stats ? number_format($stats['prob'], 2, '.', '') : '0';
                                echo "<button class='number-btn-cell $color' onclick='addNumber($i)' title='$i' data-prob='$prob'>
                                    <div class='cell-content'>
                                        <div class='cell-number'>$i</div>
                                        <div class='cell-prob'>{$prob}%</div>
                                    </div>
                                </button>";
                            } ?>
                        </div>
                    </div>

                    <!-- 2:1 выставки справа -->
                    <div class="roulette-2to1">
                        <button class="bet-btn-2to1" onclick="toggleBet(this, '1col')">2 TO 1</button>
                        <button class="bet-btn-2to1" onclick="toggleBet(this, '2col')">2 TO 1</button>
                        <button class="bet-btn-2to1" onclick="toggleBet(this, '3col')">2 TO 1</button>
                    </div>
                </div>

                <!-- Нижние выставки -->
                <div class="roulette-bottom">
                    <!-- Дюжины -->
                    <div class="dozen-row">
                        <button class="bet-btn-dozen" onclick="toggleBet(this, '1st')">1ST 12</button>
                        <button class="bet-btn-dozen" onclick="toggleBet(this, '2nd')">2ND 12</button>
                        <button class="bet-btn-dozen" onclick="toggleBet(this, '3rd')">3RD 12</button>
                    </div>

                    <!-- Колонки выставок -->
                    <div class="bets-bottom-row">
                        <button class="bet-btn-bottom" onclick="toggleBet(this, '1-18')">1 TO 18</button>
                        <button class="bet-btn-bottom" onclick="toggleBet(this, 'EVEN')">EVEN</button>
                        <button class="bet-btn-bottom red-field" onclick="toggleBet(this, 'RED')">🔴 RED</button>
                        <button class="bet-btn-bottom black-field" onclick="toggleBet(this, 'BLACK')">⚫ BLACK</button>
                        <button class="bet-btn-bottom" onclick="toggleBet(this, 'ODD')">ODD</button>
                        <button class="bet-btn-bottom" onclick="toggleBet(this, '19-36')">19 TO 36</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addNumber(num) {
            // Создаём скрытую форму и отправляем её
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'number';
            input.value = num;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function switchView(view) {
            const tableView = document.getElementById('table-view');
            const rouletteView = document.getElementById('roulette-view');
            const buttons = document.querySelectorAll('.view-toggle');

            if (view === 'table') {
                tableView.classList.add('show');
                rouletteView.classList.remove('show');
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                tableView.classList.remove('show');
                rouletteView.classList.add('show');
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }

            // Сохраняем выбор в localStorage
            localStorage.setItem('rouletteView', view);
        }

        // Восстанавливаем сохранённый вид при загрузке страницы
        window.addEventListener('load', function() {
            const savedView = localStorage.getItem('rouletteView') || 'table';
            switchView(savedView);
        });

        function toggleBet(btn, bet) {
            btn.style.opacity = btn.style.opacity === '0.5' ? '1' : '0.5';
        }
    </script>
</body>
</html>
