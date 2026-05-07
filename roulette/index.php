<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['history'])) $_SESSION['history'] = [];

// Параметры сортировки
$sortCol = $_GET['sort'] ?? 'number';
$sortOrder = $_GET['order'] ?? 'asc';

// AJAX-обновление без полной перезагрузки страницы
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    if (isset($_POST['number']) && $_POST['number'] !== '') {
        $num = (int)$_POST['number'];
        if ($num >= 0 && $num <= 36) array_unshift($_SESSION['history'], $num);
    }
    if (isset($_POST['clear'])) {
        $_SESSION['history'] = [];
    }

    $probabilities = calculateProbabilities($_SESSION['history'], $groups);
    $numberStats = getIndividualNumbersStats($_SESSION['history']);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'history' => $_SESSION['history'],
        'probabilities' => $probabilities,
        'numberStats' => $numberStats
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$probabilities = calculateProbabilities($_SESSION['history'], $groups);
$numberStats = getIndividualNumbersStats($_SESSION['history']);

$probabilityMap = [];
foreach ($probabilities as $item) {
    $probabilityMap[$item['name']] = $item;
}

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
                <form id="number-form" method="POST" onsubmit="return submitNumber(event)">
                    <input type="number" name="number" min="0" max="36" autofocus required>
                    <button type="submit" class="btn btn-add">ДОБАВИТЬ</button>
                </form>
                <form id="clear-form" method="POST" onsubmit="return clearHistory(event)">
                    <button type="submit" class="btn btn-clear">СБРОС</button>
                </form>

                <div class="view-toggle-container">
                    <button class="view-toggle active" data-view="table" onclick="switchView('table')">📊 Таблица</button>
                    <button class="view-toggle" data-view="roulette" onclick="switchView('roulette')">🎡 Рулетка</button>
                </div>
            </div>

            <div class="history-list">
                <h3>ИСТОРИЯ</h3>
                <div id="history-list">
                <?php foreach($_SESSION['history'] as $h): ?>
                    <div class="history-item"><?php echo $h; ?></div>
                <?php endforeach; ?>
                </div>
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
                            <div class="stat-row" data-stat-name="<?php echo $p['name']; ?>">
                                <span><strong><?php echo $p['name']; ?></strong> (<span class="stat-streak">S:<?php echo $p['streak']; ?></span>)</span>
                                <div style="text-align: right;">
                                    <div class="stat-prob-next" style="font-size: 0.75rem; color: #aaa;">След: <?php echo formatProb($p['prob']); ?></div>
                                    <div class="val-prob <?php echo getAnomalyClass($p['break']); ?> stat-prob-break">
                                        Риск: <?php echo formatExpectation($p['break']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- РУЛЕТКА -->
        <div id="roulette-view">
            <div class="roulette-table">
                <!-- Главный стол чисел -->
                <div class="roulette-main">
                    <!-- Зеро -->
                    <div class="roulette-zero">
                        <button id="roulette-number-0" class="number-btn-large green" onclick="addNumber(0)">
                            <div class="cell-content">
                                <div class="cell-number number-cell">0</div>
                                <?php 
                                $zeroStats = array_values(array_filter($numberStats, fn($s) => $s['number'] == 0))[0] ?? null;
                                $zeroProb = $zeroStats ? formatProb($zeroStats['prob']) : '0%';
                                ?>
                                <div class="cell-prob number-prob-cell"><?php echo $zeroProb; ?></div>
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
                                $prob = $stats ? formatProb($stats['prob']) : '0%';
                                echo "<button id='roulette-number-$i' class='number-btn-cell $color' onclick='addNumber($i)' title='$i'>
                                    <div class='cell-content'>
                                        <div class='cell-number number-cell'>$i</div>
                                        <div class='cell-prob number-prob-cell'>$prob</div>
                                    </div>
                                </button>";
                            } ?>
                        </div>

                        <!-- Строка 2: 2, 5, 8, 11, 14, 17, 20, 23, 26, 29, 32, 35 -->
                        <div class="number-row">
                            <?php for ($i = 2; $i <= 35; $i += 3) {
                                $color = getNumberColor($i);
                                $stats = array_values(array_filter($numberStats, fn($s) => $s['number'] == $i))[0] ?? null;
                                $prob = $stats ? formatProb($stats['prob']) : '0%';
                                echo "<button id='roulette-number-$i' class='number-btn-cell $color' onclick='addNumber($i)' title='$i'>
                                    <div class='cell-content'>
                                        <div class='cell-number number-cell'>$i</div>
                                        <div class='cell-prob number-prob-cell'>$prob</div>
                                    </div>
                                </button>";
                            } ?>
                        </div>

                        <!-- Строка 3: 1, 4, 7, 10, 13, 16, 19, 22, 25, 28, 31, 34 -->
                        <div class="number-row">
                            <?php for ($i = 1; $i <= 34; $i += 3) {
                                $color = getNumberColor($i);
                                $stats = array_values(array_filter($numberStats, fn($s) => $s['number'] == $i))[0] ?? null;
                                $prob = $stats ? formatProb($stats['prob']) : '0%';
                                echo "<button id='roulette-number-$i' class='number-btn-cell $color' onclick='addNumber($i)' title='$i'>
                                    <div class='cell-content'>
                                        <div class='cell-number number-cell'>$i</div>
                                        <div class='cell-prob number-prob-cell'>$prob</div>
                                    </div>
                                </button>";
                            } ?>
                        </div>
                    </div>

                    <!-- 2:1 выставки справа -->
                    <div class="roulette-2to1">
                        <?php foreach (['3rd Column', '2nd Column', '1st Column'] as $columnName):
                            $columnStats = $probabilityMap[$columnName] ?? null;
                        ?>
                        <div class="bet-btn-2to1-wrapper" data-bet-name="<?php echo $columnName; ?>">
                            <button class="bet-btn-2to1">2 TO 1</button>
                            <div class="bet-prob bet-prob-column"><?php echo $columnStats ? formatProb($columnStats['prob']) : '-'; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Нижние выставки -->
                <div class="roulette-bottom">
                    <!-- Дюжины -->
                    <div class="dozen-row">
                        <?php 
                        $dozenNames = [
                            '1st Dozen' => '1ST 12',
                            '2nd Dozen' => '2ND 12',
                            '3rd Dozen' => '3RD 12'
                        ];
                        foreach($dozenNames as $dozenKey => $dozenDisplay):
                            $dozenProb = null;
                            foreach($probabilities as $p):
                                if($p['name'] === $dozenKey) {
                                    $dozenProb = formatProb($p['prob']);
                                    break;
                                }
                            endforeach;
                        ?>
                        <div class="bet-btn-dozen-wrapper" data-bet-name="<?php echo $dozenKey; ?>">
                            <button class="bet-btn-dozen"><?php echo $dozenDisplay; ?></button>
                            <div class="bet-prob bet-prob-dozen"><?php echo $dozenProb ?: '-'; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Колонки выставок -->
                    <div class="bets-bottom-row">
                        <?php 
                        $betNames = [
                            '1-18' => '1 TO 18',
                            'Even' => 'EVEN',
                            'Red' => '🔴 RED',
                            'Black' => '⚫ BLACK',
                            'Odd' => 'ODD',
                            '19-36' => '19 TO 36'
                        ];
                        
                        foreach($betNames as $key => $display):
                            $betProb = null;
                            foreach($probabilities as $p):
                                if(($key === '1-18' && $p['name'] === '1-18') ||
                                   ($key === 'Even' && $p['name'] === 'Even') ||
                                   ($key === 'Red' && $p['name'] === 'Red') ||
                                   ($key === 'Black' && $p['name'] === 'Black') ||
                                   ($key === 'Odd' && $p['name'] === 'Odd') ||
                                   ($key === '19-36' && $p['name'] === '19-36')) {
                                    $betProb = formatProb($p['prob']);
                                    break;
                                }
                            endforeach;
                        ?>
                        <div class="bet-btn-bottom-wrapper" data-bet-name="<?php echo $key === 'Even' ? 'Even' : ($key === 'Red' ? 'Red' : ($key === 'Black' ? 'Black' : ($key === 'Odd' ? 'Odd' : $key))); ?>">
                            <button class="bet-btn-bottom <?php echo $key === 'Red' ? 'red-field' : ($key === 'Black' ? 'black-field' : ''); ?>">
                                <?php echo $display; ?>
                            </button>
                            <div class="bet-prob bet-prob-bottom"><?php echo $betProb ?: '-'; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Таблица чисел в правой колонке -->
        <div id="numbers-panel" class="panel show">
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
                            <tr data-number="<?php echo $s['number']; ?>">
                                <td><span class="num-badge number-cell"><?php echo $s['number']; ?></span></td>
                                <td class="sigma-cell"><?php echo $s['sigma']; ?></td>
                                <td class="last-seen-cell"><?php echo $s['lastSeen']; ?></td>
                                <td class="val-prob number-prob-cell"><?php echo formatProb($s['prob']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function postHistoryChange(payload) {
            const response = await fetch('?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams(payload).toString()
            });

            return response.json();
        }

        function addNumber(num) {
            return postHistoryChange({ number: num })
                .then(updateStateFromResponse)
                .then(() => false);
        }

        function submitNumber(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const input = form.querySelector('input[name="number"]');
            if (!input || input.value === '') {
                return false;
            }
            return postHistoryChange({ number: input.value })
                .then(updateStateFromResponse)
                .then(() => {
                    input.value = '';
                    input.focus();
                    return false;
                });
        }

        function clearHistory(event) {
            if (event) event.preventDefault();
            return postHistoryChange({ clear: 1 })
                .then(updateStateFromResponse)
                .then(() => false);
        }

        function switchView(view) {
            const tableView = document.getElementById('table-view');
            const rouletteView = document.getElementById('roulette-view');
            const numbersPanel = document.getElementById('numbers-panel');
            const buttons = document.querySelectorAll('.view-toggle');

            if (view === 'table') {
                tableView.classList.add('show');
                rouletteView.classList.remove('show');
                numbersPanel.classList.add('show');
                buttons[0].classList.add('active');
                buttons[1].classList.remove('active');
            } else {
                tableView.classList.remove('show');
                rouletteView.classList.add('show');
                numbersPanel.classList.remove('show');
                buttons[0].classList.remove('active');
                buttons[1].classList.add('active');
            }

            // Сохраняем выбор в localStorage
            localStorage.setItem('rouletteView', view);
        }

        function updateStateFromResponse(data) {
            updateHistoryList(data.history || []);
            updateNumberStats(data.numberStats || []);
            updateProbabilitySections(data.probabilities || []);
        }

        function updateHistoryList(history) {
            const historyList = document.getElementById('history-list');
            if (!historyList) return;

            historyList.innerHTML = history.map((value) => `<div class="history-item">${value}</div>`).join('');
        }

        function updateNumberStats(numberStats) {
            numberStats.forEach((stat) => {
                const row = document.querySelector(`tr[data-number="${stat.number}"]`);
                if (!row) return;

                const sigmaCell = row.querySelector('.sigma-cell');
                const lastSeenCell = row.querySelector('.last-seen-cell');
                const probCell = row.querySelector('.number-prob-cell');

                if (sigmaCell) sigmaCell.textContent = stat.sigma;
                if (lastSeenCell) lastSeenCell.textContent = stat.lastSeen === '&infin;' ? '∞' : stat.lastSeen;
                if (probCell) probCell.textContent = formatProbClient(stat.prob);

                const rouletteCell = document.getElementById(`roulette-number-${stat.number}`);
                if (rouletteCell) {
                    const probNode = rouletteCell.querySelector('.number-prob-cell');
                    if (probNode) probNode.textContent = formatProbClient(stat.prob);
                }
            });
        }

        function updateProbabilitySections(probabilities) {
            const probabilityMap = {};
            probabilities.forEach((item) => {
                probabilityMap[item.name] = item;
            });

            document.querySelectorAll('[data-stat-name]').forEach((row) => {
                const name = row.getAttribute('data-stat-name');
                const stat = probabilityMap[name];
                if (!stat) return;

                const streak = row.querySelector('.stat-streak');
                const follow = row.querySelector('.stat-prob-next');
                const risk = row.querySelector('.stat-prob-break');

                if (streak) streak.textContent = `S:${stat.streak}`;
                if (follow) follow.textContent = `След: ${formatProbClient(stat.prob)}`;
                if (risk) {
                    risk.className = `val-prob ${getRiskClass(stat.break)} stat-prob-break`;
                    risk.textContent = `Риск: ${formatExpectationClient(stat.break)}`;
                }
            });

            document.querySelectorAll('[data-bet-name]').forEach((wrapper) => {
                const name = wrapper.getAttribute('data-bet-name');
                const stat = probabilityMap[name];
                if (!stat) return;

                const probNode = wrapper.querySelector('.bet-prob');
                if (probNode) probNode.textContent = formatProbClient(stat.prob);
            });
        }

        function formatProbClient(num) {
            if (num < 0.000001) return '< 0.000001%';
            return `${Number(num).toFixed(4)}%`;
        }

        function formatExpectationClient(breakValue) {
            if (breakValue > 99.99 && breakValue < 100) {
                return '> 99.99%';
            }
            if (breakValue >= 100) {
                return '> 99.99%';
            }
            return `${Number(breakValue).toFixed(2)}%`;
        }

        function getRiskClass(breakValue) {
            if (breakValue >= 90) return 'risk-red';
            if (breakValue >= 70) return 'risk-orange';
            return 'risk-green';
        }

        // Восстанавливаем сохранённый вид при загрузке страницы
        window.addEventListener('load', function() {
            const savedView = localStorage.getItem('rouletteView') || 'table';
            switchView(savedView);
        });
    </script>
</body>
</html>
