<?php
// Группы чисел рулетки
$groups = [
    'Colors' => [
        'Red' => [1,3,5,7,9,12,14,16,18,19,21,23,25,27,30,32,34,36],
        'Black' => [2,4,6,8,10,11,13,15,17,20,22,24,26,28,29,31,33,35],
        'Zero' => [0]
    ],
    'Parity' => [
        'Even' => range(2, 36, 2),
        'Odd' => range(1, 35, 2)
    ],
    'Ranges' => [
        '1-18' => range(1, 18),
        '19-36' => range(19, 36)
    ],
    'Dozens' => [
        '1st Dozen' => range(1, 12),
        '2nd Dozen' => range(13, 24),
        '3rd Dozen' => range(25, 36)
    ]
];

// Обновленное форматирование
function formatProb($num) {
    if ($num < 0.000001) return "< 0.000001%";
    return number_format($num, 4, '.', '') . '%';
}

// Новая функция для вывода "Ожидания"
function formatExpectation($break) {
    // Если разница с 100% ничтожна (например, 99.9999999)
    if ($break > 99.99 && $break < 100) {
        return "> 99.99%";
    }
    // Если вдруг из-за точности float получилось ровно 100 или больше
    if ($break >= 100) {
        return "> 99.99%";
    }
    return number_format($break, 2, '.', '') . '%';
}

function calculateProbabilities($history, $groups) {
    $stats = [];
    foreach ($groups as $category => $subgroups) {
        foreach ($subgroups as $name => $numbers) {
            $streak = 0;
            foreach ($history as $num) {
                if (in_array($num, $numbers)) $streak++;
                else break;
            }

            $p = count($numbers) / 37;
            $probContinue = pow($p, $streak + 1) * 100;
            $probBreak = 100 - $probContinue;

            $stats[] = [
                'category' => $category,
                'name' => $name,
                'streak' => $streak,
                'prob' => $probContinue,
                'break' => $probBreak
            ];
        }
    }
    return $stats;
}

function getIndividualNumbersStats($history) {
    $stats = [];
    for ($i = 0; $i <= 36; $i++) {
        $sigma = count(array_keys($history, $i));
        
        $lastSeen = -1;
        foreach ($history as $index => $num) {
            if ($num == $i) { $lastSeen = $index; break; }
        }
        
        $streak = 0;
        foreach ($history as $num) {
            if ($num == $i) $streak++;
            else break;
        }

        $probNext = pow(1/37, $streak + 1) * 100;

        $stats[] = [
            'number' => $i,
            'sigma' => $sigma,
            // Заменяем прочерк на красивую бесконечность
            'lastSeen' => ($lastSeen === -1) ? '&infin;' : $lastSeen, 
            'prob' => $probNext
        ];
    }
    return $stats;
}

/*
function getAnomalyClass($break) {
    if ($break > 99.9) return 'anomaly-extreme'; // Почти невероятно
    if ($break > 95) return 'anomaly-high';    // Очень сильная серия
    return '';
}
*/

function getAnomalyClass($break) {
    if ($break >= 90) return 'risk-red';      // Аномалия! Струна натянута
    if ($break >= 70) return 'risk-orange';   // Внимание, серия затянулась
    return 'risk-green';                      // Норма
}